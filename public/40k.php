<?php include('inc/header.php'); ?>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title">Input</h3>
    </div>
    <div class="panel-body">
        <p>Go into BattleScribe, and click "Save Roster":</p>
        <img src="/butan.png" alt="BattleScribe Save button" width="300px"/>
        <p>Then put the ROSZ/ROS file here and wait for a little bit (a few seconds, tops). ButtScribe does support HTML export roster files, but you won't get the full output, as those files lack some of the necessary data. It is offered as a legacy option, but we recommend using ROS/ROSZ format for the best experience.</p>
        <form method="post" enctype="multipart/form-data" action="/post.php">
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
                <input type="file" name="list">
            </div>
        </div>
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
                <div class="checkbox">
                    <input type="checkbox" name="big_data_sheet_appreciator">GIMME THEM BIG SHEETS 
                </div>
                <div class="checkbox">
                    <input type="checkbox" name="crusade">CRUSADE MODE (uses small sheets)
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
