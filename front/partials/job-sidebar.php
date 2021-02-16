          <div class="job-listing-left">
            <div class="input-group job-listing-job-search">
              <input type="text" class="form-control job-search-value" placeholder="Search Jobs" 
              value="<?php echo esc_output($search); ?>">
              <span class="input-group-btn">
                <button type="button" class="btn btn-primary btn-blue btn-flat job-search-button">
                  <i class="fa fa-search"></i>
                </button>
              </span>
            </div>
            <hr />
            <?php if ($companies) { ?>
            <p class="job-listing-heading">
              <span class="job-listing-heading-text"><i class="fa fa-filter"></i> <?php echo lang('companies'); ?></span>
              <span class="job-listing-heading-line"></span>
            </p>
            <ul class="job-listing-filters-list">
              <?php foreach ($companies as $key => $value) { ?>
              <li><input type="checkbox" class="company-check" <?php echo jobsCheckboxSel($companiesSel, encode($value['company_id'])); ?> value="<?php echo encode($value['company_id']); ?>" /> <?php echo trimString($value['title']); ?></li>
              <?php } ?>
            </ul>
            <?php } ?>
            <hr />
            <p class="job-listing-heading">
              <span class="job-listing-heading-text"><i class="fa fa-filter"></i> <?php echo lang('departments'); ?></span>
              <span class="job-listing-heading-line"></span>
            </p>
            <ul class="job-listing-filters-list">
              <?php foreach ($departments as $key => $value) { ?>
                <li>
                  <input type="checkbox" class="department-check" <?php echo jobsCheckboxSel($departmentsSel, encode($value['department_id'])); ?> value="<?php echo encode($value['department_id']); ?>" /> <?php echo trimString($value['title']); ?></li>
              <?php } ?>
            </ul>
          </div>
