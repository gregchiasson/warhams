<?php include('inc/header.php'); ?>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title">Input</h3>
    </div>
    <div class="panel-body">
        <p>Go into BattleScribe, and click "Save Roster":</p>
        <img src="/butan.png" alt="BattleScribe Save and Save as HTML buttons" width="300px"/>
        <p>Then put the ROSZ/ROS/HTML (PRO TIP: .rosz or .ros is the better format here - .rosz doesn't always want to expand properly, but due to missing certain data fields, .html won't give you the full feature set, and is provided here as a legacy option only) file here and wait for a little bit (a few seconds, tops):</p>
        <form method="post" enctype="multipart/form-data" action="/post.php">
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
                <input type="file" name="list">
            </div>
        </div>
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
                <div class="checkbox">
                    <input type="checkbox" name="big_data_sheet_appreciator">GIMME THEM BIG SHEETS (Create larger font and size for easier reading. Otherwise you could fit two on a page usually in landscape)
                </div>
            </div>
        </div>
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
                <input type="submit" value="pres" class="btn btn-default">
            </div>
        </div>
        </form>
    </div>
</div>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title">Output</h3>
    </div>
    <div class="panel-body">
        <p>You should get a prompt to download a PDF that looks something like this:</p>
        <img src="/output_roster.png" alt="Output data roster example" width="350"/>
        <p>Followed by a bunch of these:</p>
        <img src="/output.png" alt="Output data card example" style="width:100%"/>
    </div>
</div>

<?php include('inc/footer.php'); ?>
