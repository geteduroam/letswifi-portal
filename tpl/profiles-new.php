<?php $this->layout('menu', ['title' => 'New configuration profile'])?>
<?php $this->start('content') ?>
<main>
	<h1>geteduroam<small>– eduroam authentication made easy</small></h1>
	<p>For most users, the easiest way to use geteduroam is to use one of the official apps.</p>
	<ul class="apps buttons">
		<li><a class="btn btn-default" href="<?=$this->e($app['url'])?>">Download the apps</a></li>
	</ul>
	<hr>
	<form method="post">
		<p>For advanced users, and for using eduroam on a device where no app is yet available,
			it is also possible to download a configuration profile
		<ul class="apps devices buttons">
<?php foreach($devices as $deviceName => $deviceData): ?>
			<li><button type="submit" name="device" value="<?=$this->e($deviceName)?>" class="btn btn-default"><?=$this->e($deviceData['name'])?></button></li>
<?php endforeach; ?>
		</ul>
	</form>
</main>
<?php $this->stop('content') ?>
