<?php include('inc/header.php'); ?>


<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title">Input</h3>
    </div>
    <div class="panel-body">
        <p>Go into BattleScribe, and click "Save Roster":</p>
        <img src="/butan.png" alt="BattleScribe Save buttons" width="300px"/>
        <p>Then put the ROS/ROSZ file here and wait for a little bit (couple of seconds, tops):</p>
        <form method="post" enctype="multipart/form-data" action="/post_kt.php">
            <input type="file" name="list">
            <input type="submit" value="pres">
        </form>
    </div>
</div>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title">Output</h3>
    </div>
    <div class="panel-body">
        <p>You should get a prompt to download a PDF that looks something like this:</p>
        <img src="/output_kt.png" alt="Output data card example" style="width:100%"/>
        <img src="/output_kard.png" alt="Output data card example" style="width:100%"/>
    </div>
</div>

<?php include('inc/footer.php'); ?>
