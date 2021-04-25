<?php include('inc/header.php'); ?>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title">Input</h3>
    </div>
    <div class="panel-body">
        <p>Go into BattleScribe, and click "Save the Roster":</p>
        <p><img src="/butan.png" alt="BattleScribe Save button" width="300px"/></p>
        <p>Then put the ROSZ/ROS file here and wait for a little bit.</p>
        <form method="post" enctype="multipart/form-data" action="/post_apoc.php">
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
                <input type="file" name="list">
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
        <p><img src="/output_apoc_roster.png" alt="Output data roster example" width="50%"/></p>
        <p>Followed by a bunch of these:</p>
        <p><img src="/output_apoc.png" alt="Output data card example" style="width:100%"/></p>
    </div>
</div>

<?php include('inc/footer.php'); ?>
