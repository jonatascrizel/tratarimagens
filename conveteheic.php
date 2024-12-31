<?php
set_time_limit(0);
require 'HeicToJpg.php';

$diretorio = 'imagens' . DIRECTORY_SEPARATOR;

$dh =  opendir($diretorio);
while (false !== ($filename = readdir($dh))) {
  if ($filename != '.' && $filename != '..' && $filename != 'tratadas') {
    $name = limpaName(strtolower(pathinfo($filename, PATHINFO_FILENAME)));

    //se for HEIC converte
    $fileIsHeic = Maestroerror\HeicToJpg::isHeic($diretorio.$filename);
    if ($fileIsHeic) {
      Maestroerror\HeicToJpg::convert($diretorio.$filename)->saveAs( $diretorio.$name.".jpg");
      unlink($diretorio.$filename);
    }
  }
}
closedir($dh);

function limpaName($nome){
  $nome = preg_replace('/[^a-zA-Z0-9_-]/s','_',$nome);

  return $nome;
}


