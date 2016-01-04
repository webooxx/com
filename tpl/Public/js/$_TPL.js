$_TPL = function (url, data) {
    if (typeof url != 'string' || url.length < 1) {
        return '';
    }
    if (!$_TPL.cache[url] && url.length < 500 && url.replace(/\s*$/, '').slice(-5) === '.html') {
        $.ajax(url, function (res) {
            $_TPL.cache[url] = res;
        }, false);
    }
    if ($_TPL.cache[url]) {
        url = $_TPL.cache[url];
    }
    return $_TPL.make(url, data || {});

}
$_TPL.cache = {};
$_TPL.make = function (json, data) {

    var lines = json.replace(/&lt;!--/g, '<!--').replace(/--&gt;/g, '-->').split('\n'), spStr = '>>>><<<<';
    var args;
    for (var i = 0; i < lines.length; i++) {
        var line = lines[i];
        if (/<\!--([^>]+)-->/.test(line)) {
            args = [];
            line = line.replace(/<!--([^>]+)-->/g, function () {
                args.push(arguments[1]);

                return spStr;
            });
            var fragment = line.split(spStr);

            var out = [];
            for (var l = 0; l < fragment.length; l++) {
                var arg;

                arg = fragment[l].replace(/^\s+/, '').replace(/\s+$/, '');
                arg.length >= 1 && out.push('$out.push(\'' + arg + '\')');
                if (args[l]) {
                    arg = args[l].replace(/^\s+/, '').replace(/\s+$/, '');
                    out.push((arg[0] === '=') ? '  $out.push(' + arg.slice(1) + ');' : ' ' + arg);
                }
            }

            lines[i] = '\n' + out.join('\n');
        } else {
            lines[i] = '\n  $out.push(\'' + lines[i] + '\');';
        }
    }
    lines.unshift('\nvar $out=[];\nwith($data){');
    lines.push('\n}\nreturn $out.join(\'\');\n')
    var html = lines.join('')
    var fn, ret;
    try {
        fn = new Function('$data', html);
        ret = fn(data);
    } catch (e) {

        console && console.error && console.error(e.message);
    }
    return ret;
};
