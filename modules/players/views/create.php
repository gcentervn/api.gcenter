<h1><?= $headline ?></h1>
<?= validation_errors() ?>
<div class="card">
    <div class="card-heading">
        Player Details
    </div>
    <div class="card-body">
        <?php
        echo form_open($form_location);
        echo '<div>';
        echo 'Active ';
        echo form_checkbox('active', 1, $checked=$active);
        echo '</div>';
        echo form_label('Username');
        echo form_input('username', $username, array("placeholder" => "Enter Username"));
        echo form_label('Display Name');
        echo form_input('display_name', $display_name, array("placeholder" => "Enter Display Name"));
        echo form_label('Email Address <span>(optional)</span>');
        echo form_input('email_address', $email_address, array("placeholder" => "Enter Email Address"));
        echo form_submit('submit', 'Submit');
        echo anchor($cancel_url, 'Cancel', array('class' => 'button alt'));
        echo form_close();
        ?>
    </div>
</div>