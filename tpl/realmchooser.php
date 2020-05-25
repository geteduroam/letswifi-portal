<?php $this->layout('dialog', ['title' => 'Login mismatch'])?>
<?php $this->start('content') ?>

<main>
	<p>
		Your account is not valid for <strong><?=$this->e($realmName)?></strong><?= '' ?>
<?php if ($guessRealmName): ?>
, but it might be valid for <strong><?=$this->e($guessRealmName)?></strong>.
<?php else: ?>
.
<?php endif; ?>
		You can log in with a different account, or pick a different institution.
	</p>
<?php if ($guessRealmName): ?>
	<p>
		<a href="<?=$this->e($switchRealmLink)?>" class="btn btn-default fullwidth">
			Try logging in with <strong><?=$this->e($guessRealmName)?></strong>
			as <strong><?=$this->e($guessUserId)?></strong>
			instead
		</a>
	</p>
<?php endif; ?>
	<p>
		<a href="<?=$this->e($switchUserLink)?>" class="btn btn-default fullwidth">
			Try a different account with access to <strong><?=$this->e($realmName)?></strong>
		</a>
	</p>
	<p>
		<a href="<?=$this->e($refuseRequestLink)?>" class="btn btn-default fullwidth">
			Go back and try a different institution
		</a>
	</p>
</main>

<?php $this->stop('content') ?>
