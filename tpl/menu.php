<?php $this->layout('base', ['documentClass' => '3col'])?>
<?php
$menu = [
	'home' => ['name' => 'Info', 'href' => '/'],
	'app' => ['name' => 'Apps', 'href' => '/app/'],
	'dev' => ['name' => 'Development', 'href' => '/dev/'],
];
?>
<?php $this->start('layout') ?>
<?php /*
<header>
	<p class="logout">
		foo
</header>
*/ ?>
<nav>
	<ul>
<?php foreach($menu as $tpl => $menuItem): ?>
<?php if ($menuItem['href'] === $href): ?>
		<li class="active"><a>
<?php else: ?>
		<li><a href="<?=$this->e($menuItem['href'])?>">
<?php endif; ?>
		<?=$this->e($menuItem['name'])?></a></li>
<?php endforeach; ?>
	</ul>
</nav>
<?=$this->section('content')?>
<?php $this->stop('layout') ?>
