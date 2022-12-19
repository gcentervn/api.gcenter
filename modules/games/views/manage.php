<h1><?= $headline ?></h1>
<?php
flashdata();
echo '<p>'.anchor('games/create', 'Create New Game Record', array("class" => "button")).'</p>'; 
echo Pagination::display($pagination_data);
if (count($rows)>0) { ?>
    <table id="results-tbl">
        <thead>
            <tr>
                <th colspan="12">
                    <div>
                        <div><?php
                        echo form_open('games/manage/1/', array("method" => "get"));
                        echo form_input('searchphrase', '', array("placeholder" => "Search records..."));
                        echo form_submit('submit', 'Search', array("class" => "alt"));
                        echo form_close();
                        ?></div>
                        <div>Records Per Page: <?php
                        $dropdown_attr['onchange'] = 'setPerPage()';
                        echo form_dropdown('per_page', $per_page_options, $selected_per_page, $dropdown_attr); 
                        ?></div>

                    </div>                    
                </th>
            </tr>
            <tr>
                <th>Offline</th>
                <th>Offline Status</th>
                <th>Created Date</th>
                <th>Updated Date</th>
                <th>Name</th>
                <th>Short Name</th>
                <th>Description</th>
                <th>URL Homepage</th>
                <th>URL Social</th>
                <th>URL Playnow</th>
                <th>URL Download</th>
                <th style="width: 20px;">Action</th>            
            </tr>
        </thead>
        <tbody>
            <?php 
            $attr['class'] = 'button alt';
            foreach($rows as $row) { ?>
            <tr>
                <td><?= $row->offline ?></td>
                <td><?= $row->offline_status ?></td>
                <td><?= date('l jS F Y \a\t H:i',  strtotime($row->created_date)) ?></td>
                <td><?= date('l jS F Y \a\t H:i',  strtotime($row->updated_date)) ?></td>
                <td><?= $row->name ?></td>
                <td><?= $row->short_name ?></td>
                <td><?= $row->description ?></td>
                <td><?= $row->url_homepage ?></td>
                <td><?= $row->url_social ?></td>
                <td><?= $row->url_playnow ?></td>
                <td><?= $row->url_download ?></td>
                <td><?= anchor('games/show/'.$row->id, 'View', $attr) ?></td>        
            </tr>
            <?php
            }
            ?>
        </tbody>
    </table>
<?php 
    if(count($rows)>9) {
        unset($pagination_data['include_showing_statement']);
        echo Pagination::display($pagination_data);
    }
}
?>