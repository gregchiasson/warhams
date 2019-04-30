<?php include('inc/header.php'); ?>


<!--
Uncomment if there's known outages
-->
<div class="panel panel-danger">
    <div class="panel-heading">
        <h3 class="panel-title">Ahh crap</h3>
    </div>
    <div class="panel-body">
        <p>Kill Team might not be working, but 40k lists from BattleScribe 2.02+ work again.</p>
    </div>
</div>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title">What is ButtScribe?</h3>
    </div>
    <div class="panel-body">
        <p>ButtScribe is a web application that runs off of BattleScribe output, and generates printable datasheets for the units in your army. It currently supports Warhammer 40k (8th Edition) and Kill-Team. The goal with ButtScribe was to bridge the gap in <em>printed materials</em>: the codices have nicely-formatted unit entries, but these are intended for selecting a unit's options, so it has a raft of information that might not be relevant depending on what options you took, and they don't include the specific points costs of your specific unit. That is, the codex will have all of the options for <em>a</em> Tactical Squad, but what you really want during a game is the wargear and rules for <em>your</em> specific Tactical squads, which is where ButtScribe comes in.</p>
        <p>Also, BattleScribe does, obviously, support printing army lists, as anyone who has ever played 40k can tell you. The problem is that those lists look like butt.</p>
        <p>The list of supported games is in the header up top, and if you run into any problems, feel free to ping me on Discord - ANAmal.net</p>
    </div>
</div>

<p>
    <img src="output.png" alt="Output data card example" width="700px"/>
</p>
<p>
    <img src="output_kt.png" alt="Output data card example" width="350px"/><img src="output_kard.png" alt="Output data card example" width="350px"/>
</p>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title">Release Notes</h3>
    </div>
    <div class="panel-body">
        <ul>
            <li>Last update: <strong>29 April 2019</strong></li>
            <li><strong>BattleScribe 2.02+ works properly now.</strong></li>
            <li>Updated UI.</li>
            <li>Fix a bug where certain rules/abilities weren't appearing.</li>
            <li>Added URL to printouts so people can stop asking me where I printed these.</li>
            <li>Moved points cost to header, next to power level.</li>
            <li>Fix duplicate keywords appearing in some cases.</li>
        </ul>
    </div>
</div>

<div class="panel panel-danger">
    <div class="panel-heading">
        <h3 class="panel-title">Known Issues</h3>
    </div>
    <div class="panel-body">
        <ul>
            <li>Some of the .rosz files don't want to unzip - added error detection and a workaround.</li>
            <li>Wound trackers/degrading statlines for vehicle squadrons (IG Hellhounds) don't want to work.</li>
<!--
            <li>Kill Team is missing the "new guy" and "dead" check boxes.</li>
            <li>Kill Team cards get all beefed up if there's too many abilities</li>
            <li>Kill Team Campaign Roster doesn't work, only Kill Team Roster</li>
-->
        </ul>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">Credits</h3>
    </div>
    <div class="panel-body">
        <ul>
            <li>Brains of this outfit: TheChirurgeon</li>
            <li>Code: ANAmal.net</li>
            <li>You: <a href="https://github.com/gregchiasson/warhams">submit PRs on GitHub</a></li>
        </ul>
    </div>
</div>

<?php include('inc/footer.php'); ?>
