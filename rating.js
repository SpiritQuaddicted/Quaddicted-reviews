/*
function _gr(url, div) {
    fetch(url)
        .then(response => response.text())
        .then(content => document.getElementById(div).innerHTML = content)
}
*/

function rateImg(rating, zipname) {
    const width = rating * 25;
    document.getElementById('current-rating').style.width = `${width}px`;

    const params = new URLSearchParams({ rating, zipname });
    fetch(`rating.php?${params}`, { credentials: 'include' });
}
