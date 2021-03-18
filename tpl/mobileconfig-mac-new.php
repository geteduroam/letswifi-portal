<?php $this->layout('dialog', ['title' => 'geteduroam - Apple mobileconfig'])?>
<?php $this->start('content') ?>
<main>
<h1>geteduroam<small>– eduroam authentication made easy</small></h1>
<form method="POST" action="<?=$this->e($action)?>" id="form-download">
<p>Your download will begin shortly<input type="hidden" name="device" value="<?=$this->e($device)?>"></p>
<p><button type="submit" class="btn btn-default" id="btn-download">Start download</button></p>
</form>
<p>If you have macOS Big Sur, please go to Profiles in System Preferences to complete installation</p>
</main>

<script>
	document.getElementById('form-download').submit();
	document.getElementById('btn-download').remove();
</script>
<?php $this->stop('content') ?>
