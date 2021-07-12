/*
 * Copyright (c) 2021  Lars Münchhagen
 * email: lars.muenchhagen@outlook.de
 */

function setHinweis(text){
    let elem = document.getElementById('hinweis');
    if (text==''){
        elem.innerHTML = 'Bitte die Daten immer aktuell halten.';
    }else{
        elem.innerHTML = text;
    }
}