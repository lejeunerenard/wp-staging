<div class="wpstg-notice-alert">
    <h4>
        <?php
        _e("Attention: Check carefully if this DB tables and files are safe to delete for the staging site", "wpstg")
        ?>
    </h4>

    <p>
        Clone name: <span style="background-color:#575757;color:#fff;">
        <?php echo $name; ?>
    </span>
    </p>

    <p>
        <?php
        _e(
            "Usually the preselected data can be deleted without any risk, ".
            "but in case something goes wrong you better check it first.",
            "wpstg"
        )
        ?>
    </p>
</div>

<div class="wpstg-tabs-wrapper">

    <a href="#" class="wpstg-tab-header active" data-id="#wpstg-scanning-db">
        <span class="wpstg-tab-triangle">&#9658;</span>
        <?php echo __("DB tables to remove", "wpstg")?>
    </a>

    <!-- Database -->
    <div class="wpstg-tab-section" id="wpstg-scanning-db">
        <h4 style="margin:0;">
            <?php _e("Select the tables for removal:", "wpstg")?>
        </h4>
        <?php ?>
    </div>
    <!-- /Database -->

    <a href="#" class="wpstg-tab-header" data-id="#wpstg-scanning-files">
        <span class="wpstg-tab-triangle">&#9658;</span>
        <?php echo __("Files to remove", "wpstg")?>
    </a>

    <!-- Files -->
    <div class="wpstg-tab-section" id="wpstg-scanning-files">
        <h4 style="margin:0;">
            <?php _e("Select the folders for removal. Click on a folder name to expand it:", "wpstg") ?>
        </h4>
        <?php ?>
    </div>
    <!-- /Files -->
</div>

<a href="#" class="wpstg-link-btn button-primary" id="wpstg-cancel-removing">
    <?php _e("Cancel", "wpstg")?>
</a>

<a href="#" class="wpstg-link-btn button-primary" id="wpstg-remove-clone" data-clone="<?php echo $name?>">
    <?php echo __("Remove", "wpstg")?>
</a>