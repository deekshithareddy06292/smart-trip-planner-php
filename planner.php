<?php
/* ============================================
   Smart Trip Planner — Full Transport + Route + Food + Activities + Food Experiences
   ============================================ */
session_start();
include_once "ai.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: index.php');
  exit;
}

/* ---------- Helpers ---------- */
function inr($n){ return '₹'.number_format((int)round($n),0,'.',','); }
function km($a,$b){
  $R=6371;
  $dLat=deg2rad($b['lat']-$a['lat']);
  $dLon=deg2rad($b['lon']-$a['lon']);
  $lat1=deg2rad($a['lat']); $lat2=deg2rad($b['lat']);
  $x=sin($dLat/2)**2+sin($dLon/2)**2*cos($lat1)*cos($lat2);
  return 2*$R*asin(sqrt($x));
}
function ucfirstnorm($s){ return ucfirst(strtolower(trim($s))); }

/* ---------- Cities ---------- */
$cities=[
  'Delhi'=>['lat'=>28.6139,'lon'=>77.209],
  'Agra'=>['lat'=>27.1767,'lon'=>78.0081],
  'Jaipur'=>['lat'=>26.9124,'lon'=>75.7873],
  'Mumbai'=>['lat'=>19.076,'lon'=>72.8777],
  'Goa'=>['lat'=>15.2993,'lon'=>74.124],
  'Hyderabad'=>['lat'=>17.385,'lon'=>78.4867],
  'Chennai'=>['lat'=>13.0827,'lon'=>80.2707],
  'Rishikesh'=>['lat'=>30.0869,'lon'=>78.2676],
  'Amritsar'=>['lat'=>31.633,'lon'=>74.8723],
];

/* ---------- Graph ---------- */
function buildGraph($cities){
  $edges=[['Delhi','Agra'],['Agra','Jaipur'],['Delhi','Amritsar'],['Delhi','Rishikesh'],
          ['Jaipur','Mumbai'],['Mumbai','Goa'],['Goa','Hyderabad'],['Hyderabad','Chennai']];
  $g=[];foreach($cities as $n=>$c){$g[$n]=[];}
  foreach($edges as [$a,$b]){
    $d=km($cities[$a],$cities[$b]);
    $w=['bus'=>$d*7,'train'=>$d*3.5,'flight'=>$d*6+1500,'distance'=>$d];
    $g[$a][]=['city'=>$b]+$w; $g[$b][]=['city'=>$a]+$w;
  }
  return $g;
}
function dijkstra($g,$start,$end,$mode){
  $dist=[];$prev=[];$visited=[];
  foreach($g as $n=>$_){$dist[$n]=INF;}
  $dist[$start]=0;
  while(count($visited)<count($g)){
    $u=null;$best=INF;
    foreach($g as $n=>$_){if(!isset($visited[$n])&&$dist[$n]<$best){$best=$dist[$n];$u=$n;}}
    if($u===null||$u===$end)break;
    $visited[$u]=1;
    foreach($g[$u] as $e){
      $alt=$dist[$u]+$e[$mode];
      if($alt<($dist[$e['city']]??INF)){
        $dist[$e['city']]=$alt;$prev[$e['city']]=$u;
      }
    }
  }
  $path=[];$cur=$end;$distance=0;
  if(!isset($prev[$cur])&&$cur!==$start)return['path'=>[],'cost'=>INF,'distance'=>0];
  while($cur){
    array_unshift($path,$cur);
    if(!isset($prev[$cur]))break;
    foreach($g[$prev[$cur]] as $e){
      if($e['city']===$cur){$distance+=$e['distance'];break;}
    }
    $cur=$prev[$cur];
  }
  return['path'=>$path,'cost'=>$dist[$end],'distance'=>$distance];
}

/* ---------- Static POIs ---------- */
$catalog=[
  'Delhi'=>[
    'stays'=>[['name'=>'Hotel','night'=>3000]],
    'food'=>[['avg'=>400,'names'=>['Street chaat','Butter chicken','Paratha','Kulfi']]],
    'pois'=>[
      ['name'=>"Red Fort",'time'=>2,'cost'=>500,'cat'=>['Historical']],
      ['name'=>"India Gate",'time'=>2,'cost'=>0,'cat'=>['Family']],
      ['name'=>"Qutub Minar",'time'=>2,'cost'=>600,'cat'=>['Historical']],
      ['name'=>"Connaught Place walk",'time'=>2,'cost'=>100,'cat'=>['Shopping']]
    ]
  ],
  'Agra'=>[
    'stays'=>[['name'=>'Hotel','night'=>2800]],
    'food'=>[['avg'=>350,'names'=>['Petha','Bedai & Jalebi','Mughlai curry']]],
    'pois'=>[
      ['name'=>"Taj Mahal",'time'=>3,'cost'=>1100,'cat'=>['Historical','Romantic']],
      ['name'=>"Agra Fort",'time'=>2,'cost'=>650,'cat'=>['Historical']],
      ['name'=>"Mehtab Bagh",'time'=>2,'cost'=>100,'cat'=>['Family','Romantic']],
      ['name'=>"Local food street walk",'time'=>2,'cost'=>300,'cat'=>['Food']]
    ]
  ]
];

/* ---------- Inputs ---------- */
$from=ucfirstnorm($_POST['from']);
$to=ucfirstnorm($_POST['to']);
$days=max(1,(int)$_POST['days']);
$people=max(1,(int)$_POST['people']);
$budget=max(1000,(int)$_POST['budget']);
$type=$_POST['type']??'Family';
$prefMode=$_POST['mode']??'auto';

/* ---------- Transport ---------- */
$graph=buildGraph($cities);
$modes=['bus','train','flight'];
$speeds=['bus'=>40,'train'=>70,'flight'=>600];
$fixed=['bus'=>0,'train'=>0.5,'flight'=>2];
$routes=[];
foreach($modes as $m){
  $r=dijkstra($graph,$from,$to,$m);
  $r['time']=round($r['distance']/$speeds[$m]+$fixed[$m],1);
  $routes[$m]=$r;
}
$bestMode=array_reduce($modes,function($a,$b)use($routes){return($a===null||$routes[$b]['cost']<$routes[$a]['cost'])?$b:$a;},null);
$useMode=($prefMode==='auto')?$bestMode:$prefMode;
$route=$routes[$useMode];

/* ---------- Cost Calculation ---------- */
$stayNights=max(1,$days-1);
$cityInfo=$catalog[$to]??['stays'=>[['name'=>'Hotel','night'=>3500]],'food'=>[['avg'=>300,'names'=>['Local thali','Curry','Tea']]],'pois'=>[]];
$stayChoice=$cityInfo['stays'][0];
$stayCost=$stayChoice['night']*$stayNights*$people;
$food=$cityInfo['food'][0];
$foodCost=($food['avg']??300)*3*$days*$people;

/* ---------- Activities ---------- */
$cands=$cityInfo['pois']??[];
$aiPlan=aiGenerateItinerary($to,$days,$type,$budget,$people);
$aiPois=[];
if(!empty($aiPlan)){
  foreach($aiPlan as $day){
    foreach($day['activities'] as $a){
      $aiPois[]=['name'=>$a['name'],'time'=>$a['time'],'cost'=>$a['cost'],'cat'=>$a['category']];
    }
  }
}

// merge & remove duplicates
$merged=[];$namesSeen=[];
foreach(array_merge($cands,$aiPois) as $p){
  if(!in_array($p['name'],$namesSeen)){
    $merged[]=$p;
    $namesSeen[]=$p['name'];
  }
}

/* ---------- Add filler and food activities ---------- */
$fallbacks=[
  ['name'=>"Local bazaar visit",'time'=>2,'cost'=>100,'cat'=>['Shopping']],
  ['name'=>"Cultural performance",'time'=>3,'cost'=>200,'cat'=>['Cultural']],
  ['name'=>"Temple or church visit",'time'=>2,'cost'=>0,'cat'=>['Historical']],
  ['name'=>"Nature park walk",'time'=>3,'cost'=>50,'cat'=>['Family']],
  ['name'=>"Evening city view point",'time'=>2,'cost'=>80,'cat'=>['Romantic']]
];

// also add local food experiences
foreach($food['names'] as $dish){
  $merged[]=['name'=>"Try local dish: $dish",'time'=>1.5,'cost'=>150,'cat'=>['Food']];
}

// Ensure enough activities for all days
while(count($merged) < $days * 2){
  $f = $fallbacks[array_rand($fallbacks)];
  if(!in_array($f['name'],$namesSeen)){
    $merged[]=$f;
    $namesSeen[]=$f['name'];
  }
}

shuffle($merged);
$itinerary=[];$index=0;$perDay=2;
for($d=1;$d<=$days;$d++){
  $dayItems=[];
  for($i=0;$i<$perDay;$i++){
    if(isset($merged[$index])) $dayItems[]=$merged[$index++];
  }
  $itinerary[]=['day'=>$d,'items'=>$dayItems];
}

/* ---------- Cost Summary ---------- */
$totalAct=0;
foreach($itinerary as $day){foreach($day['items'] as $it){$totalAct+=$it['cost'];}}
$cost=[
  'transport'=>$route['cost'],
  'stay'=>$stayCost,
  'food'=>$foodCost,
  'activities'=>$totalAct
];
$cost['total']=array_sum($cost);

/* ---------- Mode Colors ---------- */
$modeColors = [
  'bus' => '#7b3f00',
  'train' => '#003366',
  'flight' => '#4b0082'
];

/* ---------- ✅ Save trip data in session ---------- */
$_SESSION['trip_plan'] = [
    'meta' => [
        'from' => $from,
        'to' => $to,
        'days' => $days,
        'people' => $people,
        'type' => $type,
        'mode' => $useMode,
        'stay' => $stayChoice['name']
    ],
    'route' => $route,
    'all_routes' => $routes,
    'itinerary' => $itinerary,
    'cost' => $cost
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Trip Plan — Smart Trip Planner</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<style>
.table td, .table th { vertical-align: middle; }
.highlight { background-color: #e0f8e0; font-weight: bold; }
.route-badge {
  background-color: #003366;
  color: #ffffff;
  margin-right: 6px;
  padding: 6px 10px;
  border-radius: 8px;
  font-weight: 500;
  font-size: 14px;
  letter-spacing: 0.3px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.3);
}
.food-activity { color: #d35400; font-weight: 500; }
.arrow {
  color: #003366;
  font-weight: bold;
  margin: 0 4px;
}
</style>
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-primary mb-4">
  <div class="container d-flex justify-content-between">
    <a class="navbar-brand fw-bold" href="index.php">Smart Trip Planner</a>
    <a href="explore.php" class="btn btn-light btn-sm">Explore Mode</a>
  </div>
</nav>

<div class="container">
  <h4>Itinerary: <?=htmlspecialchars($from)?> → <?=htmlspecialchars($to)?> (<?=$days?> days)</h4>
  <p>Type: <b><?=htmlspecialchars($type)?></b> · Best Transport: <b><?=strtoupper($useMode)?></b> · Stay: <?=htmlspecialchars($stayChoice['name'])?></p>
  <hr>

  <h5>🚌 Transport Options</h5>
  <table class="table table-bordered">
    <thead class="table-light">
      <tr><th>Mode</th><th>Distance (km)</th><th>Cost (₹)</th><th>Time (hrs)</th></tr>
    </thead>
    <tbody>
      <?php foreach($routes as $mode=>$r): ?>
        <tr class="<?=($mode==$useMode)?'highlight':''?>">
          <td><?=strtoupper($mode)?></td>
          <td><?=round($r['distance'])?></td>
          <td><?=inr($r['cost'])?></td>
          <td><?=$r['time']?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <h5>🧭 Shortest Route (<?=strtoupper($useMode)?>)</h5>
  <?php if(!empty($route['path'])): ?>
    <div class="mb-3">
      <?php foreach($route['path'] as $i=>$city): ?>
        <span class="badge route-badge" style="background-color: <?=$modeColors[$useMode]?>;">
          <?=htmlspecialchars($city)?>
        </span>
        <?php if($i<count($route['path'])-1) echo "<span class='arrow'>→</span>"; ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <h5>🗓️ Daily Itinerary</h5>
  <?php foreach($itinerary as $day): ?>
  <div class="mb-3">
    <h6 class="text-primary">Day <?=$day['day']?></h6>
    <ul class="list-group">
      <?php foreach($day['items'] as $it): ?>
        <li class="list-group-item d-flex justify-content-between align-items-start <?=in_array('Food',$it['cat'])?'food-activity':''?>">
          <div>
            <div class="fw-semibold"><?=$it['name']?></div>
            <small class="text-muted"><?=implode(', ',$it['cat'])?></small>
          </div>
          <div class="text-end">
            <div><?=$it['time']?> h</div>
            <small class="text-muted"><?=inr($it['cost'])?></small>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endforeach; ?>

  <hr>
  <h5>🍽️ Local Dishes to Try</h5>
  <ul>
    <?php foreach($food['names'] as $f): ?>
      <li><?=$f?></li>
    <?php endforeach; ?>
  </ul>

  <h5>💰 Budget Summary</h5>
  <ul class="list-unstyled">
    <li>Transport: <?=inr($cost['transport'])?></li>
    <li>Stay (<?=$stayNights?> nights): <?=inr($cost['stay'])?></li>
    <li>Food (<?=$days?> days): <?=inr($cost['food'])?></li>
    <li>Activities: <?=inr($cost['activities'])?></li>
    <li><b>Total: <?=inr($cost['total'])?></b></li>
  </ul>

  <a href="download_pdf.php" class="btn btn-primary">Download PDF</a>
</div>
</body>
</html>
