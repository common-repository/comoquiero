<h2>Qcart Settings</h2>

<?php if ($error) {?>
<div class="error">
  <p>
    <strong><?php echo esc_html($error) ?></strong>
  </p>
</div>
<?php }?>

<?php if (!$error && $updated) {?>
<div class="updated">
  <p>
    <strong>Settings Updated!</strong>
  </p>
</div>
<?php }?>

<button id="qcart-cache-button" class="button button-primary">Clean AMP Caché</button>