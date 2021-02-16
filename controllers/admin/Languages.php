<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Languages extends CI_Controller
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
     * View Function to display languages list view page
     *
     * @return html/string
     */
    public function listView()
    {
        $data['page'] = lang('languages');
        $data['menu'] = 'languages';
        $this->load->view('admin/layout/header', $data);
        $this->load->view('admin/languages/list');
    }

    /**
     * Function to get data for languages jquery datatable
     *
     * @return json
     */
    public function data()
    {
        echo json_encode($this->AdminLanguageModel->languagesList());
    }    

    /**
     * View Function (for ajax) to display create or edit view page via modal
     *
     * @return html/string
     */
    public function create()
    {
        echo $this->load->view('admin/languages/create', array(), TRUE);
    }

    /**
     * View Function (for ajax) to display create or edit view page via modal
     *
     * @param integer $language_id
     * @return html/string
     */
    public function edit($language_id = NULL)
    {
        if ($language_id == 1) {
            redirect('admin/languages');
        }
        $language = objToArr($this->AdminLanguageModel->getLanguage('language_id', $language_id));

        //Getting the default lang array
        include(APPLICATION_ROOT . '/language/english/message_lang.php');
        $default = $lang;

        //Getting the selected lang array
        $entries = include(APPLICATION_ROOT . '/language/'.$language['slug'].'/message_lang.php');
        $entries = $lang;
        
        $data['page'] = lang('languages');
        $data['menu'] = 'languages';
        $this->load->view('admin/layout/header', $data);
        $this->load->view('admin/languages/edit', compact('language', 'entries', 'default'));
    }

    /**
     * Function (for ajax) to process language create or edit form request
     *
     * @return redirect
     */
    public function save()
    {
        $this->checkIfDemo();
        $this->form_validation->set_rules('title', 'Title', 'required|min_length[2]|max_length[50]|alpha');

        if ($this->form_validation->run() === FALSE) {
            echo json_encode(array(
                'success' => 'false',
                'messages' => $this->ajaxErrorMessage(array('error' => validation_errors()))
            ));
        } elseif ($this->AdminLanguageModel->valueExist('title', $this->xssCleanInput('title'))) {
            echo json_encode(array(
                'success' => 'false',
                'messages' => $this->ajaxErrorMessage(array('error' => lang('language_already_exist')))
            ));
        } else {
            $data = $this->AdminLanguageModel->storeLanguage();
            $dir = APPLICATION_ROOT . '/language/'.$data['slug'];
            $file = APPLICATION_ROOT . '/language/'.$data['slug'].'/message_lang.php';
            if (!file_exists($file)) {
                mkdir($dir, 0777, true);

                //Writing default language in new language
                include(APPLICATION_ROOT . '/language/english/message_lang.php');
                $file = fopen($file, "w");
                fwrite($file, arrayToString($lang));
                fclose($file);
            }
            echo json_encode(array(
                'success' => 'true',
                'messages' => $this->ajaxErrorMessage(array('success' => lang('language') . lang('created'))),
                'data' => $data
            ));
        }
    }

    /**
     * Function (for ajax) to process language update form request
     *
     * @return redirect
     */
    public function update()
    {
        $this->checkIfDemo();
        $this->form_validation->set_rules('title', 'Title', 'required|min_length[2]|max_length[50]');

        $edit = $this->xssCleanInput('language_id') ? $this->xssCleanInput('language_id') : false;

        if ($this->form_validation->run() === FALSE) {
            echo json_encode(array(
                'success' => 'false',
                'messages' => $this->ajaxErrorMessage(array('error' => validation_errors()))
            ));
        } elseif ($this->AdminLanguageModel->valueExist('title', $this->xssCleanInput('title'), $edit)) {
            echo json_encode(array(
                'success' => 'false',
                'messages' => $this->ajaxErrorMessage(array('error' => lang('language_already_exist')))
            ));
        } else {
            $data = $this->xssCleanInput();

            //removing some variables
            $language_title = $data['language_title'];
            $language_id = $data['language_id'];
            unset($data['language_title'], $data['language_id']);

            //Updating in db
            $this->AdminLanguageModel->storeLanguage($language_id);

            //Writing to file
            $language = objToArr($this->AdminLanguageModel->getLanguage('language_id', $language_id));
            $file = fopen(APPLICATION_ROOT . '/language/'.$language['slug'].'/message_lang.php', "w");
            fwrite($file, arrayToString($data));
            fclose($file);

            echo json_encode(array(
                'success' => 'true',
                'messages' => $this->ajaxErrorMessage(array('success' => lang('language') . ($edit ? lang('updated') : lang('created')))),
            ));
        }
    }

    /**
     * Function (for ajax) to process language change status request
     *
     * @param integer $language_id
     * @param string $status
     * @return void
     */
    public function changeStatus($language_id = null, $status = null)
    {
        $this->checkIfDemo();
        $this->AdminLanguageModel->changeStatus($language_id, $status);
    }

    /**
     * Function (for ajax) to process language change selected request
     *
     * @param integer $language_id
     * @return void
     */
    public function changeSelected($language_id = null)
    {
        $this->checkIfDemo();
        $this->AdminLanguageModel->changeSelected($language_id);

        //Writing some variables for js
        $language = objToArr($this->AdminLanguageModel->getLanguage('language_id', $language_id));
        $entries = include(APPLICATION_ROOT . '/language/'.$language['slug'].'/message_lang.php');
        $file = fopen(ASSET_ROOT . '/admin/js/cf/lang.js', "w");
        fwrite($file, arrayToStringJs($lang));
        fclose($file);
        $file = fopen(ASSET_ROOT . '/front/js/lang.js', "w");
        fwrite($file, arrayToStringJs($lang));
        fclose($file);
    }

    /**
     * Function (for ajax) to process language bulk action request
     *
     * @return void
     */
    public function bulkAction()
    {
        $this->checkIfDemo();
        $this->AdminLanguageModel->bulkAction();
    }

    /**
     * Function (for ajax) to process language delete request
     *
     * @param integer $language_id
     * @return void
     */
    public function delete($language_id)
    {
        $this->checkIfDemo();

        //Deleting directory with files
        $language = objToArr($this->AdminLanguageModel->getLanguage('language_id', $language_id));
        $dirname = APPLICATION_ROOT . '/language/'.$language['slug'];
        @array_map('unlink', glob("$dirname/*.*"));
        @rmdir($dirname);

        //Deleting from db
        $this->AdminLanguageModel->remove($language_id);
    }
}
