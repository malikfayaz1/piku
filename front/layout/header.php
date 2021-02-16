<!DOCTYPE html>
<html lang="en">
<head>
  <title><?php echo esc_output($page); ?></title>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta content="<?php echo esc_output(setting('site-keywords')); ?>" name="keywords">
  <meta content="<?php echo esc_output(setting('site-description')); ?>" name="description">
  <meta property="route" content="<?php echo base_url(); ?>">
  <meta property="token" content="<?php echo esc_output($this->security->get_csrf_hash()); ?>">

  <!-- Favicon -->
  <link href="<?php echo base_url(); ?>assets/front/images/<?php echo setting('site-favicon'); ?>" rel="icon">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,700,700i|Montserrat:300,400,500,700" rel="stylesheet">

  <!-- CSS Libraries (For External components/plugins) -->
  <link href="<?php echo base_url(); ?>assets/front/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?php echo base_url(); ?>assets/front/css/font-awesome-all.min.css" rel="stylesheet">
  <link href="<?php echo base_url(); ?>assets/front/css/dropify.min.css" rel="stylesheet">
  <link href="<?php echo base_url(); ?>assets/front/css/jquery-ui.css" rel="stylesheet">
  <link href="<?php echo base_url(); ?>assets/front/css/bootstrap-social.css" rel="stylesheet">
  <link href="<?php echo base_url(); ?>assets/front/css/bar-rating-pill.css" rel="stylesheet">
  <link href="<?php echo base_url(); ?>assets/front/plugins/iCheck/square/blue.css" rel="stylesheet">

  <!-- Internal Style files -->
  <link href="<?php echo base_url(); ?>assets/front/css/style.css" rel="stylesheet">
  <link href="<?php echo base_url(); ?>assets/front/css/custom-style.css" rel="stylesheet">
  <link href="<?php echo base_url(); ?>assets/front/css/candidatefinder.css" rel="stylesheet">
</head>

<body>

  <!--==========================
  Header
  ============================-->
  <header id="header" class="fixed-top">
    <div class="container">
      <div class="logo float-left">
        <a href="<?php echo base_url(); ?>" class="scrollto">
          <img src="<?php echo base_url(); ?>assets/front/images/<?php echo setting('site-logo'); ?>" alt="" class="img-fluid">
        </a>
      </div>
      <nav class="main-nav float-right">
        <ul>
          <li>
            <?php if (candidateSession()) { ?>
            <a class="btn btn-primary btn-sm" href="<?php echo base_url(); ?>account"
              title="<?php echo esc_output($this->session->userdata('candidate')['first_name']); ?>">
              Hi, <?php echo trimString($this->session->userdata('candidate')['first_name'], 7); ?>
            </a>
            <?php } else { ?>
            <a class="btn btn-primary btn-sm" href="<?php echo base_url(); ?>account"><?php echo lang('account'); ?></a>
            <?php } ?>
          </li>
        </ul>
      </nav><!-- .main-nav -->
    </div>
  </header><!-- #header -->