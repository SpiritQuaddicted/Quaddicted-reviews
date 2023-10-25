<?php
include '../static/_cookieconsent.html';
?>
                <div id="header">
                        <div id="quakeinjector"><img src="/static/injector64.png" alt="Small Quake Injector Logo" />Easily install and launch Quake maps with the cross-platform <a href="/tools/quake_injector">Quake Injector</a></div>
                        <a href="/">
<?php
if (rand(0, 500) <= 23) {
	echo '<img src="/static/top_rainbow.png" alt="Surprise! An alternative, glorious Quaddicted.com Logo, for the openness and diversity in the Quake scene" id="logo" /></a>';
} else {
        echo '<img src="/static/top.png" alt="Quaddicted.com Logo" id="logo" width="748" height="115" /></a>';
}
?>
                        <br />
                        <br />
                        <span id="navlinks">
                                <a href="/">Frontpage</a>
                                <a href="/forum/viewforum.php?id=5">News</a>
                                <a href="/reviews/">Maps</a>
                                <a href="/start?do=index">Wiki</a>
                                <a href="/forum/">Forum</a>
                                <a href="/help">Help</a>
                        </span>
                </div>
