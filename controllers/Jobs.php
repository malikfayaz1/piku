<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once 'vendor/autoload.php';

class Jobs extends CI_Controller
{
    /**
     * View Function to display account job listing page
     *
     * @return html/string
     */
    public function listing($page = null)
    {
        $search = urldecode($this->xssCleanInput('search', 'get'));
        $companies = $this->xssCleanInput('companies', 'get');
        $departments = $this->xssCleanInput('departments', 'get');
        $limit = setting('jobs-limit');
        $pageData['page'] = 'Job Listing | ' . setting('site-name');
        $data['page'] = 'jobs';
        $data['jobs'] = $this->JobModel->getAll($page, $search, $companies, $departments, $limit);
        $data['jobFavorites'] = $this->JobModel->getFavorites();
        $data['companies'] = $this->CompanyModel->getAll();
        $data['departments'] = $this->DepartmentModel->getAll();
        $data['pagination'] = $this->getPagination($page, $search, $companies, $departments, $limit);
        $data['search'] = $search;
        $data['companiesSel'] = $companies;
        $data['departmentsSel'] = $departments;
        $this->load->view('front/layout/header', $pageData);
        $this->load->view('front/jobs-listing', $data);
    }    

    /**
     * View Function to display jobs listing page
     *
     * @return html/string
     */
    public function detail($id = null)
    {
        $search = urldecode($this->xssCleanInput('search', 'get'));
        $companies = urldecode($this->xssCleanInput('search', 'get'));
        $departments = $this->xssCleanInput('departments', 'get');
        $data['job'] = $this->JobModel->getJob($id);
        if (!$data['job']) {
            redirect('404_override');
        }
        $data['jobFavorites'] = $this->JobModel->getFavorites();
        $data['companies'] = $this->CompanyModel->getAll();
        $data['departments'] = $this->DepartmentModel->getAll();
        $data['search'] = $search;
        $data['companiesSel'] = $companies;
        $data['departmentsSel'] = $departments;
        $data['resumes'] = $this->ResumeModel->getCandidateResumesList();
        $data['applied'] = $this->JobModel->getAppliedJobs();
        $data['resume_id'] = $this->ResumeModel->getFirstDetailedResume();

        $pageData['page'] = $data['job']['title'].' | ' . setting('site-name');

        $this->load->view('front/layout/header', $pageData);
        $this->load->view('front/job-detail', $data);
    } 

    /**
     * Function to mark jobs as favorite
     *
     * @return html/string
     */
    public function markFavorite($id = null)
    {
        if (candidateSession()) {
            if ($this->JobModel->markFavorite($id)) {
                echo json_encode(array('success' => 'true', 'messages' => ''));
            }
        } else {
            echo json_encode(array('success' => 'false', 'messages' => ''));
        }
    } 

    /**
     * Function to unmark jobs as favorite
     *
     * @return html/string
     */
    public function unmarkFavorite($id = null)
    {
        $this->JobModel->unmarkFavorite($id);
        echo json_encode(array('success' => 'true', 'messages' => ''));
    } 

    /**
     * Function to display refer job form
     *
     * @return html/string
     */
    public function referJobView()
    {
        echo $this->load->view('front/partials/refer-job', array(), TRUE);
    } 

    /**
     * Function to refer job to a person
     *
     * @return html/string
     */
    public function referJob($id = null)
    {
        $this->checkIfDemo();
        if (candidateSession()) {
            $this->form_validation->set_rules('email', 'Email', 'required|min_length[2]|max_length[50]|valid_email');
            $this->form_validation->set_rules('name', 'Name', 'trim|required|min_length[2]|max_length[50]');
            $this->form_validation->set_rules('phone', 'Phone', 'max_length[50]|numeric');

            if ($this->form_validation->run() === FALSE) {
                echo json_encode(array(
                    'success' => 'error',
                    'messages' => $this->ajaxErrorMessage(array('error' => validation_errors()))
                ));
            } else if ($this->JobModel->ifAlreadyReferred()) {
                $this->JobModel->referJob();
                echo json_encode(array(
                    'success' => 'error',
                    'messages' => $this->ajaxErrorMessage(array('error' => lang('job_is_already_referred')))
                ));
            } else {
                $this->JobModel->referJob();
                $job_id = $this->xssCleanInput('job_id');
                $name = $this->xssCleanInput('name');
                $user = candidateSession('first_name').' '.candidateSession('last_name');
                $message = $this->load->view('front/emails/refer-job', compact('job_id', 'user', 'name'), TRUE);
                $this->sendEmail(
                    $message,
                    $this->xssCleanInput('email'),
                    'Job Referred'
                );
                echo json_encode(array(
                    'success' => 'true',
                    'messages' => $this->ajaxErrorMessage(array('success' => lang('job_referred_successfully')))
                ));
            }
        } else {
            echo json_encode(array('success' => 'false', 'messages' => ''));
        }
    }

    /**
     * Function to apply to a job
     *
     * @return html/string
     */
    public function applyJob($id = null)
    {
        $this->checkIfDemo();
        if (candidateSession()) {
            if (setting('enable-multiple-resume') == 'yes') {
                $this->form_validation->set_rules('resume', 'Resume', 'required');

                $job = $this->JobModel->getJob($this->xssCleanInput('job_id'));
                $resume = $this->ResumeModel->getFirst('resumes.resume_id', decode($this->xssCleanInput('resume')));

                if ($this->form_validation->run() === FALSE) {
                    echo json_encode(array(
                        'success' => 'error',
                        'messages' => $this->ajaxErrorMessage(array('error' => validation_errors()))
                    ));
                } elseif ($job['is_static_allowed'] != 1 && $resume['type'] != 'detailed') {
                    echo json_encode(array(
                        'success' => 'error',
                        'messages' => $this->ajaxErrorMessage(array('error' => lang('you_need_to_apply_via_detailed')))
                    ));
                } else {
                    $this->JobModel->applyJob();
                    echo json_encode(array(
                        'success' => 'true',
                        'messages' => $this->ajaxErrorMessage(array('success' => lang('job_applied_successfully')))
                    ));
                }
 
            } else {
                $this->JobModel->applyJob();
                echo json_encode(array(
                    'success' => 'true',
                    'messages' => $this->ajaxErrorMessage(array('success' => lang('job_applied_successfully')))
                ));
            }
        } else {
            echo json_encode(array('success' => 'false', 'messages' => ''));
        }
    }

    /**
     * View Function to display candidate job applications page
     *
     * @param integer $page
     * @return html/string
     */
    public function jobApplicationsView($page = null)
    {
        $this->checkLogin();
        $total = $this->JobModel->getTotalAppliedJobs();
        $limit = 5;
        $data['pagination'] = $this->createPagination($page, '/account/job-applications/', $total, $limit);
        $pageData['page'] = lang('job_applications').' | ' . setting('site-name');
        $data['jobs'] = $this->JobModel->getAppliedJobsList($page, $limit);
        $data['page'] = 'applications';
        $this->load->view('front/layout/header', $pageData);
        $this->load->view('front/account-job-applications', $data);
    }

    /**
     * View Function to display candidate job favorites page
     *
     * @param integer $page
     * @return html/string
     */
    public function jobFavoritesView($page = null)
    {
        $this->checkLogin();
        $total = $this->JobModel->getTotalFavoriteJobs();
        $limit = 5;
        $data['pagination'] = $this->createPagination($page, '/account/job-favorites/', $total, $limit);
        $pageData['page'] = lang('job_favorites').' | ' . setting('site-name');
        $data['jobs'] = $this->JobModel->getFavoriteJobsList($page, $limit);
        $data['page'] = 'favorites';
        $this->load->view('front/layout/header', $pageData);
        $this->load->view('front/account-job-favorites', $data);
    }

    /**
     * View Function to display candidate job referred page
     *
     * @param integer $page
     * @return html/string
     */
    public function jobReferredView($page = null)
    {
        $this->checkLogin();
        $total = $this->JobModel->getTotalReferredJobs();
        $limit = 5;
        $data['pagination'] = $this->createPagination($page, '/account/job-referred/', $total, $limit);
        $pageData['page'] = lang('job_referred').' | ' . setting('site-name');
        $data['jobs'] = $this->JobModel->getReferredJobsList($page, $limit);
        $data['jobFavorites'] = $this->JobModel->getFavorites();
        $data['page'] = 'referred';
        $this->load->view('front/layout/header', $pageData);
        $this->load->view('front/account-job-referred', $data);
    }
    
    /**
     * Private function to create pagination for jobs listing
     *
     * @return html/string
     */
    private function getPagination($page, $search, $companies, $departments, $limit)
    {
        $total = $this->JobModel->getTotal($search, $companies, $departments);
        $url = '/jobs/';
        return $this->createPagination($page, $url, $total, $limit);
    }    
}

