var trim = function (str) {
    return str.replace(/^\s+/, '').replace(/\s+$/, '')
};

$_GET = (function () {
    var V = {}, D = document.location.search.slice(1).split('&');
    for (var i = 0; i < D.length; i++) {
        I = D[i].split('=');
        V[I[0]] = trim(decodeURI(I[1]));
    }
    V['-1'] = function () {
        var aSearch = [];
        for (s in $_GET) {
            if (s != '-1' && $_GET[s] != '') {
                aSearch.push(s + '=' + $_GET[s])
            }
        }
        document.location.search = aSearch.join('&');
    };
    return V;
})();