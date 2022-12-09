<?php
class Auth extends Trongate
{
    private $user_level_id = 1001;
    private $sceret_salt = 'taliban666';
    private $auto_active_member = true;

    function _get_data_from_app()
    {
        $posted_data = file_get_contents('php://input');
        $post_data = (array) json_decode($posted_data);
        if ($post_data) {
            $data['username'] = $post_data['username'];
            $data['password'] = $post_data['password'] ? $post_data['password'] : '';
            $data['email_address'] = $post_data['email_address'] ? $post_data['email_address'] : '';
            return $data;
        } else {
            return false;
        }
    }

    function register()
    {
        $data = $this->_get_data_from_app();

        if ($data['username'] == '' || $data['password'] == '') {
            $error = [
                "error" => [
                    "msg" => 'Máy chủ không nhận được dữ liệu !',
                    "code" => '1001',
                ]
            ];
            http_response_code(400);
            exit(json_encode($error));
        } else {
            $check_username = $this->username_check($data['username'], $data['email_address']);

            if ($check_username == false) {
                $error = [
                    "error" => [
                        "msg" => 'Tài khoản hoặc email đã có người sử dụng !',
                        "code" => '1002',
                    ]
                ];
                http_response_code(400);
                exit(json_encode($error));
            } else {
                $data['url_string'] = strtolower(url_title($data['username']));
                $data['active'] = ($this->auto_active_member == true) ? 1 : 0;
                $data['provider'] = 'gcenter';
                $data['display_name'] = 'Người chơi bí ẩn';
                $data['registed_date'] = time();
                $data['password'] = $this->_hash_string($data['password']);
                $data['count_logins'] = 0;
                $data['last_login'] = 0;
                $data['trongate_user_id'] = $this->_create_new_trongate_user();
                $data['user_token'] = ($this->auto_active_member == false) ? make_rand_str(32) : '';
                $data['code'] = make_rand_str(16);

                $player_id = $this->model->insert($data, 'players');

                if ($this->auto_active_member == false) {
                    //add your own code here for sending a confirmation email
                    $activate_url = BASE_URL . 'players/active/' . $data['user_token'];
                    $player_obj = (object) $data;
                    $this->_send_activate_account_email($player_obj, $activate_url);
                    redirect('players/check_your_email');
                } else {
                    $this->_finish_authentication($player_id);
                }
            }
        }
    }

    function username_check($username, $email_address)
    {
        $params['username'] = $username;
        $params['email_address'] = $email_address;

        if (segment(3) !== '') {
            $update_id = segment(3, 'int');
            if ($update_id == 0) {
                $player_obj = $this->model->get_one_where('code', segment(3), 'players');

                if ($player_obj == false) {
                    $error = [
                        "error" => [
                            "msg" => 'Không tìm thấy thông tin người chơi !',
                            "code" => '1003',
                        ]
                    ];
                    http_response_code(400);
                    exit(json_encode($error));
                }
                $update_id = $player_obj->id;
            }

            $params['update_id'] = $update_id;
            $sql = 'SELECT 
            * FROM players 
            WHERE (username = :username 
            AND id!= :update_id) 
            OR (email_address = :email_address AND id!= :update_id)';

            $rows = $this->model->query_bind($sql, $params, 'array');
        } else {
            $sql = 'SELECT
            * FROM players
            WHERE username = :username
            OR email_address = :email_address';
            $rows = $this->model->query_bind($sql, $params, 'array');
        }

        if (count($rows) > 0) {
            return false;
        } else {
            return true;
        }
    }

    function _hash_string($str)
    {
        $hashed_string = password_hash($str . $this->sceret_salt, PASSWORD_BCRYPT, array(
            'cost' => 11
        ));
        return $hashed_string;
    }

    function _create_new_trongate_user()
    {
        $data['user_level_id'] = $this->user_level_id;
        $data['code'] = make_rand_str(32);
        $trongate_user_id = $this->model->insert($data, 'trongate_users');
        return $trongate_user_id;
    }

    function _send_activate_account_email($member_obj, $activate_url)
    {
        //send an email inviting the user to goto the $reset url
        $data['subject'] = 'Confirm Your Account';
        $data['target_name'] = $member_obj->first_name . ' ' . $member_obj->last_name;
        $data['member_obj'] = $member_obj;
        $data['activate_url'] = $activate_url;
        $data['target_email'] = $member_obj->email_address;
        $data['msg_html'] = $this->view('msg_confirm_account', $data, true);
        $msg_plain = str_replace('</p>', '\\n\\n', $data['msg_html']);
        $data['msg_plain'] = strip_tags($msg_plain);
        //add your own code below this line for sending email
    }

    function _finish_authentication($player_id, $remember = null)
    {
        $this->module('trongate_tokens');

        if (!isset($remember)) {
            $remember = false;
        }

        $player_obj = $this->model->get_where($player_id, 'players');

        $count_logins = $player_obj->count_logins;
        $data['count_logins'] = $count_logins + 1;
        $data['last_login'] = time();
        $data['user_token'] = '';

        $this->model->update($player_id, $data, 'players');

        $token_data['user_id'] = $player_obj->trongate_user_id;

        if ($remember == true) {
            // token for 7 days
            $token_exp = 86400 * 7;
            $nowtime = time();
            $token_data['expiry_date'] = $nowtime + $token_exp;
            //generate toke & set cookie
            $token_data['set_cookie'] = true;
            $this->trongate_tokens->_generate_token($token_data);
        } else {
            //set short term token
            $_SESSION['trongatetoken'] = $this->trongate_tokens->_generate_token($token_data);
        }

        if ($player_obj->username == '') {
            $result = [
                "status" => true,
                "msg" => 'Bạn đang truy cập bằng tài khoản khách, vui lòng liên kết tài khoản Gcenter để mở khóa toàn bộ tính năng!'
            ];
            http_response_code(200);
            exit(json_encode($result));
        } else {
            $this->module('trongate_tokens');
            $player_token = $this->model->get_one_where('user_id', $player_obj->trongate_user_id, 'trongate_tokens');
            $player_role = $this->trongate_tokens->_get_user_obj($player_token->token);

            $result = [
                "status" => true,
                "msg" => 'Đăng ký thành công!',
                "trongateToken" => $player_token->token,
                "auth_user" => [
                    "id" => $player_obj->id,
                    "provider" => $player_obj->provider ?: '',
                    "username" => $player_obj->username ?: '',
                    "provider_username" => $player_obj->provider_username ?: '',
                    "display_name" => $player_obj->display_name,
                    "role" => $player_role->user_level,
                ]
            ];
            http_response_code(200);
            echo json_encode($result);
        }
    }
}
