<?php $this->layout('base', ['documentClass' => '3col'])?>
<?php $this->start('layout') ?>
<header>
	<p class="logout">
		foo
</header>
<nav>
	<ul>
		<li class="active"><a>Apps</a></li>
		<li><a href="/credentials/">Credentials</a></li>
		<li><a href="/info/">Information</a></li>
		<li><a href="/realms/">Realms</a></li>
		<li><a href="/servers/">Servers</a></li>
		<li><a href="/users/">Users</a></li>
	</ul>
</nav>
<?=$this->section('content')?>
<?php $this->stop('layout') ?>
