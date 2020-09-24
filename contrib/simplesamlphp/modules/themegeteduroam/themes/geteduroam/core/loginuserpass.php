<!DOCTYPE html>
<html lang="en" class="dialog">
<link rel="stylesheet" href="/assets/geteduroam.css">
<link rel="icon" href="/assets/geteduroam.ico" type="image/x-icon">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo $this->t('{login:user_pass_header}'); ?></title>

<?php
$this->data['header'] = $this->t('{login:user_pass_header}');

if (strlen($this->data['username']) > 0) {
	$this->data['autofocus'] = 'password';
} else {
	$this->data['autofocus'] = 'username';
}

?>

<main>
	<h1>Login<small><?php echo (isset($this->data['header']) ? $this->data['header'] : 'SimpleSAMLphp'); ?></small></h1>

		<form action="?" method="post" id="loginform">
			<p><span><?php echo $this->t('{login:username}'); ?>:</span><input
				id="username"
				<?php echo ($this->data['forceUsername']) ? ' disabled="disabled"' : ''; ?>
				type="text"
				name="username"
				required
				<?php if (!$this->data['forceUsername']) {
					echo 'tabindex="1"';
					if (!$this->data['username']) {
						echo ' autofocus';
					}
				} ?>
				value="<?php echo htmlspecialchars($this->data['username']); ?>">
			<p><span><?php echo $this->t('{login:password}'); ?>:</span><input
				id="password"
				type="password"
				tabindex="2"
				name="password"
				required
				<?php if ($this->data['username']) {
					echo ' autofocus';
				} ?>
				>
<?php
if (array_key_exists('organizations', $this->data)) {
	?>
		<p><label for="organization"><?php echo $this->t('{login:organization}'); ?></label></p>
		<p><select name="organization" tabindex="3">
				<?php
				if (array_key_exists('selectedOrg', $this->data)) {
					$selectedOrg = $this->data['selectedOrg'];
				} else {
					$selectedOrg = null;
				}

				foreach ($this->data['organizations'] as $orgId => $orgDesc) {
					if (is_array($orgDesc)) {
						$orgDesc = $this->t($orgDesc);
					}

					if ($orgId === $selectedOrg) {
						$selected = 'selected="selected" ';
					} else {
						$selected = '';
					}

					echo '<option '.$selected.'value="'.htmlspecialchars($orgId).'">'.htmlspecialchars($orgDesc).'</option>';
				}
				?>
			</select></p>
<?php
}
?>
<?php
if ($this->data['errorcode'] !== null) {
	?>
		<p class="error"><?php
			echo htmlspecialchars($this->t(
				'{errors:title_'.$this->data['errorcode'].'}',
				$this->data['errorparams']
			)); ?></p>
<?php
}

?>

	<p><button id="submit" type="submit" class="btn btn-default" tabindex="6"><?php echo $this->t('{login:login_button}'); ?></button>
	<?php
	foreach ($this->data['stateparams'] as $name => $value) {
		echo('<input type="hidden" name="'.htmlspecialchars($name).'" value="'.htmlspecialchars($value).'" />');
	}
	?>
		</form>
	</main>