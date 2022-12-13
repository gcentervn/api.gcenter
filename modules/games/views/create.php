<h1><?= $headline ?></h1>
<?= validation_errors() ?>
<div class="card">
    <div class="card-heading">
        Game Details
    </div>
    <div class="card-body">
        <?php
        echo form_open($form_location);
        echo '<div>';
        echo 'Offline ';
        echo form_checkbox('offline', 1, $checked=$offline);
        echo '</div>';
        echo form_label('Offline Status <span>(optional)</span>');
        echo form_input('offline_status', $offline_status, array("placeholder" => "Enter Offline Status"));
        echo form_label('Created Date');
        $attr = array("class"=>"datetime-picker", "autocomplete"=>"off", "placeholder"=>"Select Created Date");
        echo form_input('created_date', $created_date, $attr);
        echo form_label('Updated Date <span>(optional)</span>');
        $attr = array("class"=>"datetime-picker", "autocomplete"=>"off", "placeholder"=>"Select Updated Date");
        echo form_input('updated_date', $updated_date, $attr);
        echo form_label('Name');
        echo form_input('name', $name, array("placeholder" => "Enter Name"));
        echo form_label('Short Name <span>(optional)</span>');
        echo form_input('short_name', $short_name, array("placeholder" => "Enter Short Name"));
        echo form_label('Description');
        echo form_input('description', $description, array("placeholder" => "Enter Description"));
        echo form_label('Detail Information');
        echo form_textarea('detail_information', $detail_information, array("placeholder" => "Enter Detail Information"));
        echo form_label('URL Homepage');
        echo form_input('url_homepage', $url_homepage, array("placeholder" => "Enter URL Homepage"));
        echo form_label('URL Social');
        echo form_input('url_social', $url_social, array("placeholder" => "Enter URL Social"));
        echo form_label('URL Playnow <span>(optional)</span>');
        echo form_input('url_playnow', $url_playnow, array("placeholder" => "Enter URL Playnow"));
        echo form_label('URL Download <span>(optional)</span>');
        echo form_input('url_download', $url_download, array("placeholder" => "Enter URL Download"));
        echo form_submit('submit', 'Submit');
        echo anchor($cancel_url, 'Cancel', array('class' => 'button alt'));
        echo form_close();
        ?>
    </div>
</div>