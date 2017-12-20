#!/usr/bin/php
<?php
require("myZenity.php");
require("myTemplate.php");
define("MinerCPUDir", "xmrig-cpu-orig");
define("MinerGPUDir", "xmrig-nvidia-gpu-cuda9");

define("MinerCfgTemplate", "config.template.json");
define("MinerCfgFile",     "config.json");

$gpuConfig = "";
$datetime = date("Ymd-His");
$developerWallets = array(
  "ITNS"=>"iz4DDGDfYRgRZhiVP1yxMaf4Z7hwTiDeZg6XndjVFkKKNfzFGiCnS7djPgRUfoRR3M7dDizt4r4U9JGthxc59Qhe35mWtZP4k",
  "XUN"=>"Xun3jHiqh1BVimz5Cs3naXQX1zP8BT7wCaZGTf7j6ZMC3FgJpHFV5nnSjenWJdkp658zEfaz1c2gfT3hsttZKtJM2ALdeZcQt5",
  "XMR"=>"45uA2XwRvMXFy9SzB9qLYahPGbJyBR2yDHoHJh4ehS8VAu3xK8DwAcDinqSHfPoXgtb6cgTQh272oanacRpLYnnQNiNSphr",
  "BCN"=>"29cZi4Kbwce9B7HesPS4aAUBpMhvy6w8w75cvXHwHWrwjkWrUGtKwzsLjuMFR7mVZNEUaBHULwGCpdmEeBsYv8W19GpGFiX",
  "FLB"=>"TFLBgqz2f2wMnhw1X2wpViVANYY6vkgXSeQRvRNE61LWfM59FUW6otQWajEaMsk7vK7E62J4BX3hWXn7R25aKGx616wJ1o9UwEy",
  "DERO"=>"dERopbTVeEHC9yYjqkfKbzeyppgFuv4YN3mqXtb1BWscfvhkKaNFkzCJQiQBatm2Cg7pk11HARF5HLCRPcFRcPTJ8Q4tCQXVWi",
  "MSR"=>"",
  "B2B"=>"",
);

$myZenity = new myZenity("Wizard");

// $myZenity->setWSize("320x240");
// $myZenity->debug("true");

$q = $myZenity->question("Este es el wizard para configurar el minado de monedas cryptonight, ¿deseas continuar a configurar el minero?", "¡Simón!", "Nel pastel");
if ($q == "1"){
  exit(0);
}
$numCpus = trim(`cat /proc/cpuinfo | grep processor | wc -l`);
$numNvidiaGpus = trim(`nvidia-smi -L|wc -l`);
$nvidiaGpuList = trim(`nvidia-smi --query-gpu=index,name,uuid,memory.total --format=csv,noheader`);

// test
/* 
$numNvidiaGpus = 0;
// $nvidiaGpuList.="\n"."1, Quadro 4001, GPU-1624f4a6-ff85-82f5-0012-e46721aea1d5, 1979 MiB";
$nvidiaGpuList="";
*/
$aesCpu = trim(`cat /proc/cpuinfo | grep flags | grep aes|wc -l`);

// GPUs array


$recommendCpus = (int)($numCpus/2>0)?$numCpus/2:1;
$aes = ($aesCpu>0)?"(Con AES)":"(Sin AES)";
$aesB = ($aesCpu>0)?true:false;


$myZenity->formCreate("CPUs detectados $aes: $numCpus, ¿Cuántos deseas utilizar?,\nRecomendados:$recommendCpus");
$myZenity->formAdd("numcpu|Cpus", "NUM", "$recommendCpus! 1..$numCpus! 1 ! 0");
$myZenity->formAdd("maxcpu|Max CPU %", "NUM", "90! 0..100! 1 ! 0");
if (!$aesB)
  $myZenity->formAdd("CPUs sin AES, tienden a minar muy lento", "LBL");
$myZenity->formAdd("Max CPU %: Para máquinas virtuales puede ir hasta 100%, pero para maquinas físicas se recomienda por debajo", "LBL");
$cpu = $myZenity->formShow();

$cpuCores = $cpu['response']['numcpu']; //<------
$maxCpu = $cpu['response']['maxcpu']; // <------
$affinityHex = dechex(pow(2, $cpuCores)-1); // <------- 

if ($numNvidiaGpus>0){
  $q = $myZenity->question("Detectamos GPUs Nvidia, ¿deseas utilizarlos para minar?", "Shi", "Ño");
  if ($q=="0"){
    // LOGICA PARA GPUs    
    $nvidiaRows = explode("\n", $nvidiaGpuList);
    
    foreach($nvidiaRows as $k => $row){
      list($index, $m, $u, $mb) = explode(",", $row);
      $uuid[$index] = trim($u);
      $model[$index] = trim($m);
      $mem[$index] = trim($mb);

      $myZenity->formCreate("GPU[$index]:".$model[$index]);
      $myZenity->formAdd("#index#|GPUIndex", "RO", "$index");
      $myZenity->formAdd("#threads#|Threads", "NUM", "30! 0..255! 1 ! 0");
      $myZenity->formAdd("#blocks#|Blocks", "NUM", "16! 0..255! 1 ! 0");
      $myZenity->formAdd("#bfactor#|BFactor", "NUM", "8! 0..255! 1 ! 0");
      $myZenity->formAdd("#bsleep#|BSleep", "NUM", "100! 0..255! 1 ! 0");
      $myZenity->formAdd("ApplyAll|Aplicar misma configuracion al resto", "CHK", "false");
      $gpuOpts[$index] = $myZenity->formShow();
      // $gpuOpts[$index]['response']
      $gpuOpts[$index]['response']['#threads#'] = intval($gpuOpts[$index]['response']['#threads#']);
      $gpuOpts[$index]['response']['#blocks#'] = intval($gpuOpts[$index]['response']['#blocks#']);
      $gpuOpts[$index]['response']['#bfactor#'] = intval($gpuOpts[$index]['response']['#bfactor#']);
      $gpuOpts[$index]['response']['#bsleep#'] = intval($gpuOpts[$index]['response']['#bsleep#']);
      $myTemplate = new myTemplate("Miners/xmrig-nvidia-gpu-cuda9/threads.template.json", $gpuOpts[$index]['response']); 
      $gpuConfig .= ",".$myTemplate->translation;
    }
      $gpuConfig = ltrim($gpuConfig, ", "); // <-----
  }else{

  }
}



// print_r($gpuOpts);

// $myZenity->debug("true");
$myZenity->setWSize("600x300");
$coinToMine = $myZenity->radio("Selecciona la moneda a minar", "Selección|Moneda|Descripcion","sel|coin|desc", 
array("ITNS|Intense coin (VPN)", 
      "XUN|Ultranote coin (Encrypted messaging)", 
      "BCN|Bytecoin (meeh)", 
      "XMR|Monero (muy mainstream)", 
      "B2B|Payment for Bussiness and People (meeh)",
      "MSR|Masari, XMR clone (meeh)",
      "FLB|Freelabit freelances coin (looks promising)",
      "DERO|Monero smart contract fork (what?)",
      ), 0);
$coin = $coinToMine['response']['coin'];
@list($myWallet, $response) = @explode("\n", $myZenity->entry("Wallet address?")); // <------
if (strlen($myWallet)<10){
  $myWallet = $developerWallets[$coin];
} 


$myZenity->resetWSize();
$cmd = "cd config.pools/$coin/; ls|cat";
$listPools = explode("\n", trim(`$cmd`));
$poolSelect = $myZenity->radio("Selecciona el pool", "Seleccion|Pool", "sel|pool", $listPools, 0);
$pool = $poolSelect['response']['pool'];
$poolConfig = json_decode(file_get_contents("config.pools/$coin/$pool"));

$arrDiffPort = $poolConfig->portDiffComment;

foreach($arrDiffPort as $k=>$v){
  $diffPortArr[] = $v->port."|".$v->diff."|".$v->comment;
}
$portSelect = $myZenity->radio("Selecciona el puerto/dificultad", "Seleccion|Puerto|Difficulty|Comment", "sel|port|diff|comment", $diffPortArr, 0);
$poolUrl = $poolConfig->poolUrl; // <-----
$poolPort = $portSelect['response']['port']; // <-----
$keepAlive = $poolConfig->keepAlive; // <-----


// CUDA CONFIG
if ($numNvidiaGpus>0){
  $pairs = array( "#keepAlive#" => $poolConfig->keepAlive, "#poolUrl#" => $poolUrl, "#poolPort#" => $poolPort, "#myWallet#" => $myWallet, "#threads#" => $gpuConfig) ;
  $myTemplate = new myTemplate("Miners/xmrig-nvidia-gpu-cuda9/config.template.json", $pairs); 
  $gpuConfigJson = $myTemplate->translation.PHP_EOL;
  @rename("Miners/".MinerGPUDir."/".MinerCfgFile, "Miners/".MinerGPUDir."/".MinerCfgFile.".old-$datetime"); 
  $o = file_put_contents("Miners/".MinerGPUDir."/".MinerCfgFile, $gpuConfigJson);
}

// CPU CONFIG
$pairs = array( "#affinityHex#" => $affinityHex, "#maxCPU#" => $maxCpu, "#keepAlive#" => $poolConfig->keepAlive, 
                "#poolUrl#" => $poolUrl, "#poolPort#" => $poolPort, "#myWallet#" => $myWallet);                
$myTemplate = new myTemplate("Miners/xmrig-cpu-orig/config.template.json", $pairs); 
$cpuConfigJson = $myTemplate->translation.PHP_EOL;
@rename("Miners/".MinerCPUDir."/".MinerCfgFile, "Miners/".MinerCPUDir."/".MinerCfgFile.".old-$datetime"); 
$o = file_put_contents("Miners/".MinerCPUDir."/".MinerCfgFile, $cpuConfigJson);

$q = $myZenity->question("La configuración se ha guardado, ¿deseas probar la configuración?", "¡Simón!", "Nel pastel");
if ($q == "1"){
  echo "config.json saved".PHP_EOL;
  exit(0);
}else{
  echo "config.json saved, starting miners".PHP_EOL;
  if ($numNvidiaGpus>0){ 
    $c = "bash -c 'cd Miners; nohup ./both.sh&'";
    $e = trim(`$c`);
  }else{
    $c = "bash -c 'cd Miners; nohup ./cpu.sh&'";
    $e = trim(`$c`);
  }
}
