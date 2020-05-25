<?php $this->layout('dialog', ['title' => 'Authorize'])?>
<?php $this->start('content') ?>
<form method="post">
<?php if (null !== $logoutUrl): ?>
<header>
	<p class="logout">
		<a href="<?=$this->e($logoutUrl)?>" class="btn-txt">
			Not <?=$this->e($userId)?>?
		</a>
	</p>
</header>
<?php endif; ?>
<main>
	<p>Do you want to use your account to connect to eduroam on this device?</p>
	<p class="text-center"><button type="submit" class="btn btn-default" name="<?=$this->e($postField)?>" value="<?=$this->e($postValue)?>">Approve</button></p>
	<hr>
	<details>
		<summary>Why is this needed?</summary>
		<p>By clicking approve, you allow the application to receive Wi-Fi profiles on your behalf.</p>
	</details>
</main>
</form>
<?php $this->stop('content') ?>
