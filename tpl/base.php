<!DOCTYPE html>
<html lang="en" class="<?=$this->e($documentClass)?>">
<link rel="stylesheet" href="/assets/geteduroam.css">
<link rel="icon" href="/assets/geteduroam.ico" type="image/x-icon">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php if (isset($meta_redirect)): ?>
<meta http-equiv="refresh" content="0; url=<?=$this->e($meta_redirect)?>">
<?php endif; ?>
<title>geteduroam - <?=$this->e($title)?></title>
<?=$this->section('layout')?>
