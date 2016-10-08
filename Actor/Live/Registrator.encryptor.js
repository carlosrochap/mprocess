function PackageSAData(e, d) {
    var a = [],
        c = 0;
    a[c++] = 1;
    a[c++] = 1;
    a[c++] = 0;
    var b, g = d.length;
    a[c++] = g * 2;
    for (b = 0; b < g; b++) {
        a[c++] = d.charCodeAt(b) & 255;
        a[c++] = (d.charCodeAt(b) & 65280) >> 8
    }
    var f = e.length;
    a[c++] = f;
    for (b = 0; b < f; b++) a[c++] = e.charCodeAt(b) & 127;
    return a
}
function PackagePwdOnly(d) {
    var a = [],
        b = 0;
    a[b++] = 1;
    a[b++] = 1;
    a[b++] = 0;
    a[b++] = 0;
    var c, e = d.length;
    a[b++] = e;
    for (c = 0; c < e; c++) a[b++] = d.charCodeAt(c) & 127;
    return a
}
function PackagePinOnly(e) {
    var a = [],
        b = 0;
    a[b++] = 1;
    a[b++] = 2;
    a[b++] = 0;
    a[b++] = 0;
    a[b++] = 0;
    var c, d = e.length;
    a[b++] = d;
    for (c = 0; c < d; c++) a[b++] = e.charCodeAt(c) & 127;
    return a
}
function PackageLoginIntData(c) {
    var b = [],
        d = 0,
        a;
    for (a = 0; a < c.length; a++) {
        b[d++] = c.charCodeAt(a) & 255;
        b[d++] = (c.charCodeAt(a) & 65280) >> 8
    }
    return b
}
function PackageSADataForProof(c) {
    var b = [],
        d = 0,
        a;
    for (a = 0; a < c.length; a++) {
        b[d++] = c.charCodeAt(a) & 127;
        b[d++] = (c.charCodeAt(a) & 65280) >> 8
    }
    return b
}
function PackageNewPwdOnly(d) {
    var a = [],
        b = 0;
    a[b++] = 1;
    a[b++] = 1;
    var c, e = d.length;
    a[b++] = e;
    for (c = 0; c < e; c++) a[b++] = d.charCodeAt(c) & 127;
    a[b++] = 0;
    a[b++] = 0;
    return a
}
function PackageNewAndOldPwd(f, e) {
    var a = [],
        c = 0;
    a[c++] = 1;
    a[c++] = 1;
    var b, d = e.length;
    a[c++] = d;
    for (b = 0; b < d; b++) a[c++] = e.charCodeAt(b) & 127;
    a[c++] = 0;
    d = f.length;
    a[c++] = d;
    for (b = 0; b < d; b++) a[c++] = f.charCodeAt(b) & 127;
    return a
}
function mapByteToBase64(a) {
    if (a >= 0 && a < 26) return String.fromCharCode(65 + a);
    else if (a >= 26 && a < 52) return String.fromCharCode(97 + a - 26);
    else if (a >= 52 && a < 62) return String.fromCharCode(48 + a - 52);
    else if (a == 62) return "+";
    else if (a == 63) return "/"
}
function base64Encode(b, d) {
    var a, c = "";
    for (a = d; a < 4; a++) b = b >> 6;
    for (a = 0; a < d; a++) {
        c = mapByteToBase64(b & 63) + c;
        b = b >> 6
    }
    return c
}
function byteArrayToBase64(d) {
    var f = d.length,
        b = "",
        a, c;
    for (a = f - 3; a >= 0; a -= 3) {
        c = d[a] | d[a + 1] << 8 | d[a + 2] << 16;
        b = b + base64Encode(c, 4)
    }
    var e = f % 3;
    c = 0;
    for (a += 2; a >= 0; a--) c = c << 8 | d[a];
    if (e == 0) b = b + base64Encode(c, 4);
    else if (e == 1) b = b + base64Encode(c << 16, 2) + "==";
    else if (e == 2) b = b + base64Encode(c << 8, 3) + "=";
    return b
}
function parseRSAKeyFromString(b) {
    var c = b.indexOf(";");
    if (c < 0) return null;
    var f = b.substr(0, c),
        e = b.substr(c + 1),
        a = f.indexOf("=");
    if (a < 0) return null;
    var g = f.substr(a + 1);
    a = e.indexOf("=");
    if (a < 0) return null;
    var h = e.substr(a + 1),
        d = {};
    d["n"] = hexStringToMP(h);
    d["e"] = parseInt(g, 16);
    return d
}
function RSAencrypt(a, e, h) {
    var c = e.n,
        i = e.e,
        j = a.length,
        f = c.size * 2,
        g = 42;
    if (j + g > f) return null;
    applyPKCSv2Padding(a, f, h);
    a = a.reverse();
    var k = byteArrayToMP(a),
        d = modularExp(k, i, c);
    d.size = c.size;
    var b = mpToByteArray(d);
    b = b.reverse();
    return b
}
function JSMPnumber() {
    this.size = 1;
    this.data = [];
    this.data[0] = 0
}
function duplicateMP(b) {
    var a = new JSMPnumber;
    a.size = b.size;
    a.data = b.data.slice(0);
    return a
}
function byteArrayToMP(c) {
    var b = new JSMPnumber,
        a = 0,
        d = c.length,
        e = d >> 1;
    for (a = 0; a < e; a++) b.data[a] = c[2 * a] + (c[1 + 2 * a] << 8);
    if (d % 2) b.data[a++] = c[d - 1];
    b.size = a;
    return b
}
function mpToByteArray(c) {
    var b = [],
        a = 0,
        d = c.size;
    for (a = 0; a < d; a++) {
        b[a * 2] = c.data[a] & 255;
        b[a * 2 + 1] = c.data[a] >>> 8
    }
    return b
}
function modularExp(f, b, e) {
    var g = [],
        c = 0;
    while (b > 0) {
        g[c] = b & 1;
        b = b >>> 1;
        c++
    }
    var a = duplicateMP(f);
    for (var d = c - 2; d >= 0; d--) {
        a = modularMultiply(a, a, e);
        if (g[d] == 1) a = modularMultiply(a, f, e)
    }
    return a
}
function modularMultiply(e, d, b) {
    var c = multiplyMP(e, d),
        a = divideMP(c, b);
    return a.r
}
function multiplyMP(d, f) {
    var c = new JSMPnumber;
    c.size = d.size + f.size;
    var a, b;
    for (a = 0; a < c.size; a++) c.data[a] = 0;
    var e = d.data,
        h = f.data,
        g = c.data;
    if (d == f) {
        for (a = 0; a < d.size; a++) g[2 * a] += e[a] * e[a];
        for (a = 1; a < d.size; a++) for (b = 0; b < a; b++) g[a + b] += 2 * e[a] * e[b]
    } else for (a = 0; a < d.size; a++) for (b = 0; b < f.size; b++) g[a + b] += e[a] * h[b];
    normalizeJSMP(c);
    return c
}
function normalizeJSMP(d) {
    var b, c, e, a, g, f;
    e = d.size;
    c = 0;
    for (b = 0; b < e; b++) {
        a = d.data[b];
        a += c;
        f = a;
        c = Math.floor(a / 65536);
        a -= c * 65536;
        d.data[b] = a
    }
}
function removeLeadingZeroes(a) {
    var b = a.size - 1;
    while (b > 0 && a.data[b--] == 0) a.size--
}
function divideMP(a, b) {
    var j = a.size,
        d = b.size,
        l = b.data[d - 1],
        k = b.data[d - 1] + b.data[d - 2] / 65536,
        h = new JSMPnumber;
    h.size = j - d + 1;
    a.data[j] = 0;
    for (var e = j - 1; e >= d - 1; e--) {
        var f = e - d + 1,
            c = Math.floor((a.data[e + 1] * 65536 + a.data[e]) / k);
        if (c > 0) {
            var g = multiplyAndSubtract(a, c, b, f);
            if (g < 0) {
                c--;
                multiplyAndSubtract(a, c, b, f)
            }
            while (g > 0 && a.data[e] >= l) {
                g = multiplyAndSubtract(a, 1, b, f);
                if (g > 0) c++
            }
        }
        h.data[f] = c
    }
    var i = {};
    i["q"] = h;
    removeLeadingZeroes(a);
    i["r"] = a;
    return i
}
function multiplyAndSubtract(f, i, g, d) {
    var a, h = f.data.slice(0),
        b = 0,
        e = f.data;
    for (a = 0; a < g.size; a++) {
        var c = b + g.data[a] * i;
        b = c >>> 16;
        c = c - b * 65536;
        if (c > e[a + d]) {
            e[a + d] += 65536 - c;
            b++
        } else e[a + d] -= c
    }
    if (b > 0) e[a + d] -= b;
    if (e[a + d] < 0) {
        f.data = h.slice(0);
        return -1
    }
    return +1
}
function applyPKCSv2Padding(d, f, l) {
    var n = d.length,
        a, m = [218, 57, 163, 238, 94, 107, 75, 13, 50, 85, 191, 239, 149, 96, 24, 144, 175, 216, 7, 9],
        h = f - n - 40 - 2,
        e = [];
    for (a = 0; a < h; a++) e[a] = 0;
    e[h] = 1;
    var o = m.concat(e, d),
        c = [];
    for (a = 0; a < 20; a++)
        c[a] = 0;//Math.floor(Math.random() * 256);
    c = SHA1(c.concat(l));
    var k = MGF(c, f - 21),
        g = XORarrays(o, k),
        j = MGF(g, 20),
        i = XORarrays(c, j),
        b = [];
    b[0] = 0;
    b = b.concat(i, g);
    for (a = 0; a < b.length; a++) d[a] = b[a]
}
function MGF(f, d) {
    if (d > 4096) return null;
    var a = f.slice(0),
        b = a.length;
    a[b++] = 0;
    a[b++] = 0;
    a[b++] = 0;
    a[b] = 0;
    var e = 0,
        c = [];
    while (c.length < d) {
        a[b] = e++;
        c = c.concat(SHA1(a))
    }
    return c.slice(0, d)
}
function XORarrays(b, d) {
    if (b.length != d.length) return null;
    var c = [],
        e = b.length;
    for (var a = 0; a < e; a++) c[a] = b[a] ^ d[a];
    return c
}
function SHA1(e) {
    var c, d = e.slice(0);
    PadSHA1Input(d);
    var a = {};
    a["A"] = 1732584193;
    a["B"] = 4023233417;
    a["C"] = 2562383102;
    a["D"] = 271733878;
    a["E"] = 3285377520;
    for (c = 0; c < d.length; c += 64) SHA1RoundFunction(a, d, c);
    var b = [];
    wordToBytes(a.A, b, 0);
    wordToBytes(a.B, b, 4);
    wordToBytes(a.C, b, 8);
    wordToBytes(a.D, b, 12);
    wordToBytes(a.E, b, 16);
    return b
}
function wordToBytes(b, d, c) {
    var a;
    for (a = 3; a >= 0; a--) {
        d[c + a] = b & 255;
        b = b >>> 8
    }
}
function PadSHA1Input(b) {
    var d = b.length,
        e = d,
        f = d % 64,
        g = f < 55 ? 56 : 120,
        a;
    b[e++] = 128;
    for (a = f + 1; a < g; a++) b[e++] = 0;
    var c = d * 8;
    for (a = 1; a < 8; a++) {
        b[e + 8 - a] = c & 255;
        c = c >>> 8
    }
}
function SHA1RoundFunction(b, l, m) {
    var n = 1518500249,
        o = 1859775393,
        p = 2400959708,
        q = 3395469782,
        a, g, k, h = [],
        f = b.A,
        c = b.B,
        d = b.C,
        e = b.D,
        i = b.E;
    for (g = 0, k = m; g < 16; g++, k += 4) h[g] = l[k] << 24 | l[k + 1] << 16 | l[k + 2] << 8 | l[k + 3] << 0;
    for (g = 16; g < 80; g++) h[g] = rotateLeft(h[g - 3] ^ h[g - 8] ^ h[g - 14] ^ h[g - 16], 1);
    var j;
    for (a = 0; a < 20; a++) {
        j = rotateLeft(f, 5) + (c & d | ~c & e) + i + h[a] + n & 4294967295;
        i = e;
        e = d;
        d = rotateLeft(c, 30);
        c = f;
        f = j
    }
    for (a = 20; a < 40; a++) {
        j = rotateLeft(f, 5) + (c ^ d ^ e) + i + h[a] + o & 4294967295;
        i = e;
        e = d;
        d = rotateLeft(c, 30);
        c = f;
        f = j
    }
    for (a = 40; a < 60; a++) {
        j = rotateLeft(f, 5) + (c & d | c & e | d & e) + i + h[a] + p & 4294967295;
        i = e;
        e = d;
        d = rotateLeft(c, 30);
        c = f;
        f = j
    }
    for (a = 60; a < 80; a++) {
        j = rotateLeft(f, 5) + (c ^ d ^ e) + i + h[a] + q & 4294967295;
        i = e;
        e = d;
        d = rotateLeft(c, 30);
        c = f;
        f = j
    }
    b.A = b.A + f & 4294967295;
    b.B = b.B + c & 4294967295;
    b.C = b.C + d & 4294967295;
    b.D = b.D + e & 4294967295;
    b.E = b.E + i & 4294967295
}
function rotateLeft(b, a) {
    var c = b >>> 32 - a,
        e = (1 << 32 - a) - 1,
        d = b & e;
    return d << a | c
}
function hexStringToMP(e) {
    var a, d, b = Math.ceil(e.length / 4),
        c = new JSMPnumber;
    c.size = b;
    for (a = 0; a < b; a++) {
        d = e.substr(a * 4, 4);
        c.data[b - 1 - a] = parseInt(d, 16)
    }
    return c
}

function Encrypt(key, random_num, a, c, h, d) {
    var b = [];
    switch (h.toLowerCase()) {
    case "chgsqsa":
        b = PackageSAData(a, c);
        break;
    case "chgpwd":
        b = PackageNewAndOldPwd(a, d);
        break;
    case "pwd":
        b = PackagePwdOnly(a);
        break;
    case "pin":
        b = PackagePinOnly(a);
        break;
    case "proof":
        b = PackageLoginIntData(a || c);
        break;
    case "saproof":
        b = PackageSADataForProof(c);
        break;
    case "newpwd":
        b = PackageNewPwdOnly(d)
    default:
        return null
    }
    return byteArrayToBase64(RSAencrypt(b, parseRSAKeyFromString(key), random_num));
}

// "e=10001;m=cc2d949089b687346fd8e89907ca07ca56f8328b00bad95e3a58d1d0856974d6623ac267ec90f41a6ca7ce15f0771822ab2151c9b6e546cda781d0d6c134b517262aaf8a7a368fe0933356c405a27c7ccedc4edeec8d0395277ff0c3bf48c13a08e2ccf453a1257df3c03d1c9f4b8164c8e6ed3202919d6d5da1e247f9331799", '9eef479af45dd5c85faed22d87bdf39d747c278a0115aec4a0cc39b1f4b9b36c2a2c5e3dcc0a5164742573d90148b68c7ddeb40214d580cac4917083dfef6ca3735d59c738945ef4f79aae159295e5dac1afea7c3ef008901db4110bf03fe3fb559af317', 'poiuyt', '', 'pwd', ''
key = readline();
randomNum = readline();
pwd = readline();
sqsa = readline();
type = readline();
newPwd = readline();
print(Encrypt(key, randomNum, pwd, sqsa, type, newPwd))
