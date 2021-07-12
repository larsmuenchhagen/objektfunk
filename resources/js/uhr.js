/*
 * Copyright (c) 2021  Lars Münchhagen
 * email: lars.muenchhagen@outlook.de
 */

function uhrzeit() {
	let jetzt = new Date(),
		d = jetzt.getDate();
		M = jetzt.getMonth();
		y = jetzt.getFullYear();
        h = jetzt.getHours();
        m = jetzt.getMinutes();
        s = jetzt.getSeconds();
	d = fuehrendeNull(d);
	M = fuehrendeNull(M);
	h = fuehrendeNull(h);
    m = fuehrendeNull(m);
    s = fuehrendeNull(s);
    document.getElementById('uhr').innerHTML = d + '.' + M + '.'+ y + ' ' + h + ':' + m + ':' + s;
    setTimeout(uhrzeit, 500);
  }
  
  function fuehrendeNull(zahl) {
    zahl = (zahl < 10 ? '0' : '' )+ zahl;  
    return zahl;
  }