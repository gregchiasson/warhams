<?php include('inc/header.php'); ?>


<div class="panel panel-danger">
    <div class="panel-heading">
        <h3 class="panel-title">Ahh crap</h3>
    </div>
    <div class="panel-body">
        <p>Sorry folks, but it looks like the latest BattleScribe update (2.02.01.112 or so) changed up the way ros/rosz files get created, and ButtScribe has some compatibility issues. That was always going to happen sooner or later, but I believe it to be a relatively simple fix, and I'm working on it.</p>

        <p>For 40k lists, you can work around this by uploading HTML lists, but Kill Team support isn't working at all. I'm hoping to have an update to ButtScribe out soon, likely by April 28th, to address this.</p>

        <p>Apologies for the temporary outage, but this is the sort of thing that happens when you're piggy-backing on someone else's tools. It'll get fixed ASAP, just bear with me, and thanks to everyone who reported the issues.</p>
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
            <li>Updated UI.</li>
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
            <li>Mixed "soup" detachments are all beefed up.</li>
            <li>IG squads show up with "1 guardsman".</li>
            <li>Kill Team is missing the "new guy" and "dead" check boxes.</li>
            <li>Kill Team cards get all beefed up if there's too many abilities</li>
            <li>Kill Team Campaign Roster doesn't work, only Kill Team Roster</li>
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
