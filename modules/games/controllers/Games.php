<?php
class Games extends Trongate
{

    private $default_limit = 20;
    private $per_page_options = array(10, 20, 50, 100);

    function _init_filezone_settings()
    {
        $data['targetModule'] = 'games';
        $data['destination'] = 'games_pictures';
        $data['max_file_size'] = 1200;
        $data['max_width'] = 2500;
        $data['max_height'] = 1400;
        $data['upload_to_module'] = true;
        return $data;
    }

    function get_all_games()
    {
        api_auth();
        $result = $this->model->get_where_custom('offline', 0, '=', 'created_date');
        foreach ($result as $key => $game) {
            $result[$key]->categories = $this->_set_game_category($game->id);
            $result[$key]->os_systems = $this->_set_game_os_system($game->id);
        }

        http_response_code(200);
        echo json_encode((object) $result);
    }

    function _set_game_category($id)
    {
        $result = [];
        $params['id'] = $id;
        $sql = 'SELECT
        games_categories.`name`
        FROM
        games_categories
        INNER JOIN
        associated_games_and_games_categories
        ON 
            games_categories.id = associated_games_and_games_categories.games_categories_id
        WHERE
        associated_games_and_games_categories.games_id = :id';

        $rows = $this->model->query_bind($sql, $params, 'object');

        foreach ($rows as $row) {
            $result[] = $row->name;
        }

        return json_encode((object) $result);
    }

    function _set_game_os_system($id)
    {
        $result = [];
        $params['id'] = $id;
        $sql = 'SELECT
        games_os_systems.`name`
        FROM
        games_os_systems
        INNER JOIN
        associated_games_and_games_os_systems
        ON 
            games_os_systems.id = associated_games_and_games_os_systems.games_os_systems_id
        WHERE
        associated_games_and_games_os_systems.games_id = :id';

        $rows = $this->model->query_bind($sql, $params, 'object');

        foreach ($rows as $row) {
            $result[] = $row->name;
        }

        return json_encode((object) $result);
    }

    function create()
    {
        $this->module('trongate_security');
        $this->trongate_security->_make_sure_allowed();

        $update_id = (int) segment(3);
        $submit = post('submit');

        if (($submit == '') && ($update_id > 0)) {
            $data = $this->_get_data_from_db($update_id);
        } else {
            $data = $this->_get_data_from_post();
        }

        if ($update_id > 0) {
            $data['headline'] = 'Update Game Record';
            $data['cancel_url'] = BASE_URL . 'games/show/' . $update_id;
        } else {
            $data['headline'] = 'Create New Game Record';
            $data['cancel_url'] = BASE_URL . 'games/manage';
        }

        $data['form_location'] = BASE_URL . 'games/submit/' . $update_id;
        $data['view_file'] = 'create';
        $this->template('admin', $data);
    }

    function manage()
    {
        $this->module('trongate_security');
        $this->trongate_security->_make_sure_allowed();

        if (segment(4) !== '') {
            $data['headline'] = 'Search Results';
            $searchphrase = trim($_GET['searchphrase']);
            $params['offline_status'] = '%' . $searchphrase . '%';
            $params['name'] = '%' . $searchphrase . '%';
            $params['short_name'] = '%' . $searchphrase . '%';
            $params['description'] = '%' . $searchphrase . '%';
            $params['url_homepage'] = '%' . $searchphrase . '%';
            $params['url_social'] = '%' . $searchphrase . '%';
            $params['url_playnow'] = '%' . $searchphrase . '%';
            $params['url_download'] = '%' . $searchphrase . '%';
            $sql = 'select * from games
            WHERE offline_status LIKE :offline_status
            OR name LIKE :name
            OR short_name LIKE :short_name
            OR description LIKE :description
            OR url_homepage LIKE :url_homepage
            OR url_social LIKE :url_social
            OR url_playnow LIKE :url_playnow
            OR url_download LIKE :url_download
            ORDER BY id';
            $all_rows = $this->model->query_bind($sql, $params, 'object');
        } else {
            $data['headline'] = 'Manage Games';
            $all_rows = $this->model->get('id');
        }

        $pagination_data['total_rows'] = count($all_rows);
        $pagination_data['page_num_segment'] = 3;
        $pagination_data['limit'] = $this->_get_limit();
        $pagination_data['pagination_root'] = 'games/manage';
        $pagination_data['record_name_plural'] = 'games';
        $pagination_data['include_showing_statement'] = true;
        $data['pagination_data'] = $pagination_data;

        $data['rows'] = $this->_reduce_rows($all_rows);
        $data['selected_per_page'] = $this->_get_selected_per_page();
        $data['per_page_options'] = $this->per_page_options;
        $data['view_module'] = 'games';
        $data['view_file'] = 'manage';
        $this->template('admin', $data);
    }

    function show()
    {
        $this->module('trongate_security');
        $token = $this->trongate_security->_make_sure_allowed();
        $update_id = (int) segment(3);

        if ($update_id == 0) {
            redirect('games/manage');
        }

        $data = $this->_get_data_from_db($update_id);
        $data['offline'] = ($data['offline'] == 1 ? 'yes' : 'no');
        $data['token'] = $token;

        if ($data == false) {
            redirect('games/manage');
        } else {
            //generate picture folders, if required
            $picture_settings = $this->_init_picture_settings();
            $this->_make_sure_got_destination_folders($update_id, $picture_settings);

            //attempt to get the current picture
            $column_name = $picture_settings['target_column_name'];

            if ($data[$column_name] !== '') {
                //we have a picture - display picture preview
                $data['draw_picture_uploader'] = false;
                $picture = $data['picture'];

                if ($picture_settings['upload_to_module'] == true) {
                    $module_assets_dir = BASE_URL . segment(1) . MODULE_ASSETS_TRIGGER;
                    $data['picture_path'] = $module_assets_dir . '/' . $picture_settings['destination'] . '/' . $update_id . '/' . $picture;
                } else {
                    $data['picture_path'] = BASE_URL . $picture_settings['destination'] . '/' . $update_id . '/' . $picture;
                }
            } else {
                //no picture - draw upload form
                $data['draw_picture_uploader'] = true;
            }
            $data['update_id'] = $update_id;
            $data['headline'] = 'Game Information';
            $data['filezone_settings'] = $this->_init_filezone_settings();
            $data['view_file'] = 'show';
            $this->template('admin', $data);
        }
    }

    function _reduce_rows($all_rows)
    {
        $rows = [];
        $start_index = $this->_get_offset();
        $limit = $this->_get_limit();
        $end_index = $start_index + $limit;

        $count = -1;
        foreach ($all_rows as $row) {
            $count++;
            if (($count >= $start_index) && ($count < $end_index)) {
                $row->offline = ($row->offline == 1 ? 'yes' : 'no');
                $rows[] = $row;
            }
        }

        return $rows;
    }

    function submit()
    {
        $this->module('trongate_security');
        $this->trongate_security->_make_sure_allowed();

        $submit = post('submit', true);

        if ($submit == 'Submit') {

            $this->validation_helper->set_rules('offline_status', 'Offline Status', 'min_length[2]|max_length[255]');
            $this->validation_helper->set_rules('created_date', 'Created Date', 'required|valid_datetimepicker_us');
            $this->validation_helper->set_rules('updated_date', 'Updated Date', 'valid_datetimepicker_us');
            $this->validation_helper->set_rules('name', 'Name', 'min_length[2]|max_length[255]|required');
            $this->validation_helper->set_rules('short_name', 'Short Name', 'min_length[2]|max_length[255]');
            $this->validation_helper->set_rules('description', 'Description', 'min_length[2]|max_length[255]|required');
            $this->validation_helper->set_rules('detail_information', 'Detail Information', 'required|min_length[2]');
            $this->validation_helper->set_rules('url_homepage', 'URL Homepage', 'min_length[2]|max_length[255]|required');
            $this->validation_helper->set_rules('url_social', 'URL Social', 'min_length[2]|max_length[255]|required');
            $this->validation_helper->set_rules('url_playnow', 'URL Playnow', 'min_length[2]|max_length[255]');
            $this->validation_helper->set_rules('url_download', 'URL Download', 'min_length[2]|max_length[255]');

            $result = $this->validation_helper->run();

            if ($result == true) {

                $update_id = (int) segment(3);
                $data = $this->_get_data_from_post();
                $data['url_string'] = strtolower(url_title($data['name']));
                $data['updated_date'] = str_replace(' at ', '', $data['updated_date']);
                $data['updated_date'] = date('Y-m-d H:i', strtotime($data['updated_date']));
                $data['created_date'] = str_replace(' at ', '', $data['created_date']);
                $data['created_date'] = date('Y-m-d H:i', strtotime($data['created_date']));
                $data['offline'] = ($data['offline'] == 1 ? 1 : 0);

                if ($update_id > 0) {
                    //update an existing record
                    $this->model->update($update_id, $data, 'games');
                    $flash_msg = 'The record was successfully updated';
                } else {
                    //insert the new record
                    $update_id = $this->model->insert($data, 'games');
                    $flash_msg = 'The record was successfully created';
                }

                set_flashdata($flash_msg);
                redirect('games/show/' . $update_id);
            } else {
                //form submission error
                $this->create();
            }
        }
    }

    function submit_delete()
    {
        $this->module('trongate_security');
        $this->trongate_security->_make_sure_allowed();

        $submit = post('submit');
        $params['update_id'] = (int) segment(3);

        if (($submit == 'Yes - Delete Now') && ($params['update_id'] > 0)) {
            //delete all of the comments associated with this record
            $sql = 'delete from trongate_comments where target_table = :module and update_id = :update_id';
            $params['module'] = 'games';
            $this->model->query_bind($sql, $params);

            //delete the record
            $this->model->delete($params['update_id'], 'games');

            //set the flashdata
            $flash_msg = 'The record was successfully deleted';
            set_flashdata($flash_msg);

            //redirect to the manage page
            redirect('games/manage');
        }
    }

    function _get_limit()
    {
        if (isset($_SESSION['selected_per_page'])) {
            $limit = $this->per_page_options[$_SESSION['selected_per_page']];
        } else {
            $limit = $this->default_limit;
        }

        return $limit;
    }

    function _get_offset()
    {
        $page_num = (int) segment(3);

        if ($page_num > 1) {
            $offset = ($page_num - 1) * $this->_get_limit();
        } else {
            $offset = 0;
        }

        return $offset;
    }

    function _get_selected_per_page()
    {
        $selected_per_page = (isset($_SESSION['selected_per_page'])) ? $_SESSION['selected_per_page'] : 1;
        return $selected_per_page;
    }

    function set_per_page($selected_index)
    {
        $this->module('trongate_security');
        $this->trongate_security->_make_sure_allowed();

        if (!is_numeric($selected_index)) {
            $selected_index = $this->per_page_options[1];
        }

        $_SESSION['selected_per_page'] = $selected_index;
        redirect('games/manage');
    }

    function _get_data_from_db($update_id)
    {
        $record_obj = $this->model->get_where($update_id, 'games');

        if ($record_obj == false) {
            $this->template('error_404');
            die();
        } else {
            $data = (array) $record_obj;
            return $data;
        }
    }

    function _get_data_from_post()
    {
        $data['offline'] = post('offline', true);
        $data['offline_status'] = post('offline_status', true);
        $data['created_date'] = post('created_date', true);
        $data['updated_date'] = post('updated_date', true);
        $data['name'] = post('name', true);
        $data['short_name'] = post('short_name', true);
        $data['description'] = post('description', true);
        $data['detail_information'] = post('detail_information', true);
        $data['url_homepage'] = post('url_homepage', true);
        $data['url_social'] = post('url_social', true);
        $data['url_playnow'] = post('url_playnow', true);
        $data['url_download'] = post('url_download', true);
        return $data;
    }

    function _init_picture_settings()
    {
        $picture_settings['max_file_size'] = 2000;
        $picture_settings['max_width'] = 1200;
        $picture_settings['max_height'] = 1200;
        $picture_settings['resized_max_width'] = 450;
        $picture_settings['resized_max_height'] = 450;
        $picture_settings['destination'] = 'games_pics';
        $picture_settings['target_column_name'] = 'picture';
        $picture_settings['thumbnail_dir'] = 'games_pics_thumbnails';
        $picture_settings['thumbnail_max_width'] = 120;
        $picture_settings['thumbnail_max_height'] = 120;
        $picture_settings['upload_to_module'] = true;
        return $picture_settings;
    }

    function _make_sure_got_destination_folders($update_id, $picture_settings)
    {

        $destination = $picture_settings['destination'];

        if ($picture_settings['upload_to_module'] == true) {
            $target_dir = APPPATH . 'modules/' . segment(1) . '/assets/' . $destination . '/' . $update_id;
        } else {
            $target_dir = APPPATH . 'public/' . $destination . '/' . $update_id;
        }

        if (!file_exists($target_dir)) {
            //generate the image folder
            mkdir($target_dir, 0777, true);
        }

        //attempt to create thumbnail directory
        if (strlen($picture_settings['thumbnail_dir']) > 0) {
            $ditch = $destination . '/' . $update_id;
            $replace = $picture_settings['thumbnail_dir'] . '/' . $update_id;
            $thumbnail_dir = str_replace($ditch, $replace, $target_dir);
            if (!file_exists($thumbnail_dir)) {
                //generate the image folder
                mkdir($thumbnail_dir, 0777, true);
            }
        }
    }

    function submit_upload_picture($update_id)
    {

        $this->module('trongate_security');
        $this->trongate_security->_make_sure_allowed();

        if ($_FILES['picture']['name'] == '') {
            redirect($_SERVER['HTTP_REFERER']);
        }

        $submit = post('submit');

        if ($submit == 'Upload') {
            $picture_settings = $this->_init_picture_settings();
            extract($picture_settings);

            $validation_str = 'allowed_types[gif,jpg,jpeg,png]|max_size[' . $max_file_size . ']|max_width[' . $max_width . ']|max_height[' . $max_height . ']';
            $this->validation_helper->set_rules('picture', 'item picture', $validation_str);

            $result = $this->validation_helper->run();

            if ($result == true) {

                $config['destination'] = $destination . '/' . $update_id;
                $config['max_width'] = $resized_max_width;
                $config['max_height'] = $resized_max_height;

                if ($thumbnail_dir !== '') {
                    $config['thumbnail_dir'] = $thumbnail_dir . '/' . $update_id;
                    $config['thumbnail_max_width'] = $thumbnail_max_width;
                    $config['thumbnail_max_height'] = $thumbnail_max_height;
                }

                //upload the picture
                $config['upload_to_module'] = (!isset($picture_settings['upload_to_module']) ? false : $picture_settings['upload_to_module']);
                $this->upload_picture($config);

                //update the database
                $data[$target_column_name] = $_FILES['picture']['name'];
                $this->model->update($update_id, $data);

                $flash_msg = 'The picture was successfully uploaded';
                set_flashdata($flash_msg);
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                redirect($_SERVER['HTTP_REFERER']);
            }
        }
    }

    function ditch_picture($update_id)
    {

        if (!is_numeric($update_id)) {
            redirect($_SERVER['HTTP_REFERER']);
        }

        $this->module('trongate_security');
        $this->trongate_security->_make_sure_allowed();

        $result = $this->model->get_where($update_id);

        if ($result == false) {
            redirect($_SERVER['HTTP_REFERER']);
        }

        $picture_settings = $this->_init_picture_settings();
        $target_column_name = $picture_settings['target_column_name'];
        $picture_name = $result->$target_column_name;

        if ($picture_settings['upload_to_module'] == true) {
            $picture_path = APPPATH . 'modules/' . segment(1) . '/assets/' . $picture_settings['destination'] . '/' . $update_id . '/' . $picture_name;
        } else {
            $picture_path = APPPATH . 'public/' . $picture_settings['destination'] . '/' . $update_id . '/' . $picture_name;
        }

        $picture_path = str_replace('\\', '/', $picture_path);

        if (file_exists($picture_path)) {
            unlink($picture_path);
        }

        if (isset($picture_settings['thumbnail_dir'])) {
            $ditch = $picture_settings['destination'] . '/' . $update_id;
            $replace = $picture_settings['thumbnail_dir'] . '/' . $update_id;
            $thumbnail_path = str_replace($ditch, $replace, $picture_path);

            if (file_exists($thumbnail_path)) {
                unlink($thumbnail_path);
            }
        }

        $data[$target_column_name] = '';
        $this->model->update($update_id, $data);

        $flash_msg = 'The picture was successfully deleted';
        set_flashdata($flash_msg);
        redirect($_SERVER['HTTP_REFERER']);
    }
}
