<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Settings extends CI_Controller
{
    /**
     * Constructor
     * 
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->checkAdminLogin();
    }

    /**
     * View Function to display update app view
     *
     * @return html/string
     */
    public function updateApplication()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        ini_set('max_execution_time', 1000);

        $data['page'] = lang('update_application');
        $data['menu'] = 'update_application';

        $current = $this->UpdateModel->getCurrent();
        $currentVersion = isset($current['version']) ? $current['version'] : '1.0';
        $updateLink = 'http://code-wand.com/updates/cf/index.php?current='.$currentVersion.'&testing=true&pc='.setting('purchase-code');
        $latest = remoteRequest($updateLink);
        $latest = objToArr(json_decode($latest));

        $latestVersion = isset($latest['version']) ? $latest['version'] : '';

        if ($latest == 'not_allowed') {
            dd('Please send your purchase code via our codecanyon support link');
        } else if ($currentVersion == $latestVersion || $latest == '') {
            $latest = 'Your application is up to date';
            $updates = $this->UpdateModel->getAll();
        } else {
            foreach ($latest['files'] as $file) {
                $remoteFile = 'http://code-wand.com/updates/cf/files.php?version='.$latestVersion.'&pc='.setting('purchase-code').'&file='.$file[1].'.txt';

                $content = remoteRequest($remoteFile);

                //Creating any new directory if not exists
                if (!file_exists(MAIN_ROOT.$file[0])) {
                    mkdir(MAIN_ROOT.$file[0], 0777, true);
                }
                
                if (is_writable(MAIN_ROOT.$file[0])) {
                    $new = MAIN_ROOT.$file[0].'/'.$file[1].'.'.$file[2];
                    if (file_exists($new)) {
                        rename($new, MAIN_ROOT.$file[0].'/'.$file[1].'-old-'.$currentVersion.'.'.$file[2]);
                    }
                    createFile($file[0].'/'.$file[1].'.'.$file[2], $content);
                } else {
                    die('Directory is not writeable, Please do manual update');
                }
            }
            $latest['files'] = json_encode($latest['files']);
            $latest['is_current'] = 1;
            $this->UpdateModel->store($latest);
            $this->SchemaModel->run();
            $this->SchemaQuestionsModel->run();
            $this->DataModel->run();
            $this->DataQuestionsModel->run();
            redirect('admin/settings/update-app');
        }

        $this->load->view('admin/layout/header', $data);
        $this->load->view('admin/settings/update', compact('updates', 'latest'));
    }

    /**
     * View Function to display general settings page view
     *
     * @return html/string
     */
    public function general()
    {
        $data['page'] = lang('general_settings');
        $data['menu'] = 'general_settings';
        $settings = $this->AdminSettingModel->getSettingsByCategory('General');
        $this->load->view('admin/layout/header', $data);
        $this->load->view('admin/settings/general', compact('settings'));
    }

    /**
     * View Function to display api settings page view
     *
     * @return html/string
     */
    public function apis()
    {
        $data['page'] = lang('api_settings');
        $data['menu'] = 'api_settings';
        $settings = $this->AdminSettingModel->getSettingsByCategory('Apis');
        $this->load->view('admin/layout/header', $data);
        $this->load->view('admin/settings/apis', compact('settings'));
    }

    /**
     * View Function to display css settings page view
     *
     * @return html/string
     */
    public function css()
    {
        $data['page'] = lang('css_settings');
        $data['menu'] = 'css_settings';
        $css = file_get_contents(ASSET_ROOT . '/front/css/custom-style.css');
        $this->load->view('admin/layout/header', $data);
        $this->load->view('admin/settings/css', compact('css'));
    }

    /**
     * View Function to display language settings page view
     *
     * @return html/string
     */
    public function home()
    {
        $data['page'] = lang('home_page_settings');
        $data['menu'] = 'home_page_settings';
        $settings = $this->AdminSettingModel->getSettingsByCategory('Home');
        $this->load->view('admin/layout/header', $data);
        $this->load->view('admin/settings/home', compact('settings'));
    }

    /**
     * Function (for ajax) to process settings update form request
     *
     * @return redirect
     */
    public function updateSettings()
    {
        $this->checkIfDemo();
        $data = $this->xssCleanInput();

        $this->doValidation();

        if ($_FILES) {
            $result  = $this->uploadImage();
            if (!$result['success']) {
                echo json_encode(array(
                    'success' => 'false',
                    'messages' => $this->ajaxErrorMessage(array('error' => $result['messages']))
                ));
                exit;
            } else {
                $data = array_merge($data, $result['messages']);
            }
        }

        $this->AdminSettingModel->updateSetting($data);

        echo json_encode(array(
            'success' => 'true',
            'messages' => $this->ajaxErrorMessage(array('success' => lang('settings_updated')))
        ));
    }

    /**
     * Function (for ajax) to process css update form request
     *
     * @return redirect
     */
    public function updateCss()
    {
        $this->checkIfDemo();
        $data = $this->xssCleanInput();
        $file = fopen(ASSET_ROOT . '/front/css/custom-style.css',"w");
        fwrite($file,$data['css-editor']);
        fclose($file);
        echo json_encode(array(
            'success' => 'true',
            'messages' => $this->ajaxErrorMessage(array('success' => lang('settings_updated')))
        ));
    }

    /**
     * Private function to upload image if any
     *
     * @param integer $user_id
     * @return array
     */
    private function uploadImage()
    {
        $this->checkIfDemo();
        $data = array();
        foreach ($_FILES as $key => $file) {
            if ($file['name'] != '') {
                $file = explode('.', $file['name']);
                $ext = $file[1];
                $filename = $key == 'site-logo' ? $key.'-original' : $key;
                $config['upload_path'] = ASSET_ROOT . '/front/images/';
                $config['allowed_types'] = 'gif|jpg|png';
                $config['file_name'] = $filename;
                //$config['max_size'] = '1024';
                //$config['max_width'] = '200';
                //$config['max_height'] = '200';
                $this->load->library('upload', $config);
                if ($key != 'site-logo') {
                    $this->deleteImages('before', $key);
                }
                if (!$this->upload->do_upload($key)) {
                    return array(
                        'success' => false,
                        'messages' => $this->upload->display_errors()
                    );
                } else {
                    $filepath = ASSET_ROOT . '/front/images/'.$key.'.'.$ext;
                    if ($key == 'site-logo' && file_exists($filepath)) {
                        $this->deleteImages('before', $key);
                        $this->resizeByWidthOrHeight(ASSET_ROOT . '/front/images/', $key, $filename, $ext, 0, 45);
                        $this->deleteImages('after', $key);
                    } elseif ($key == 'site-favicon'  && file_exists($filepath)) {
                        $this->resizeByWidthOrHeight(ASSET_ROOT . '/front/images/', $key, $filename, $ext, 16, 16);
                    }
                    $data[$key] = $key.'.'.$ext;
                }
            }
        }
        return array('success' => true, 'messages' => $data);
    }

    /**
     * Function to do validation for setting variables
     *
     * @return json
     */
    public function doValidation()
    {   
        $validate = false;
        if ($this->xssCleanInput('site-name')) {
        $this->form_validation->set_rules('site-name', 'Site Name', 'trim|alpha_numeric_spaces|required|min_length[2]|max_length[50]');
        $validate = true;
        }
        if ($this->xssCleanInput('admin-email')) {
        $validate = true;
        $this->form_validation->set_rules('admin-email', 'Admin Email', 'required|valid_email|max_length[50]');
        }
        
        if ($this->form_validation->run() === FALSE && $validate) {
            echo json_encode(array(
                'success' => 'false',
                'messages' => $this->ajaxErrorMessage(array('error' => validation_errors()))
            ));
            exit;
        }
    }

    private function deleteImages($time, $key)
    {
        if ($time == 'before') {
            foreach (array('png', 'jpg', 'jpeg', 'gif') as $ext) {
                @unlink(ASSET_ROOT.'/front/images/'.$key.'.'.$ext);
            }
        } else {
            foreach (array('png', 'jpg', 'jpeg', 'gif') as $ext) {
                @unlink(ASSET_ROOT.'/front/images/'.$key.'-original.'.$ext);
            }
        }
    }
}
