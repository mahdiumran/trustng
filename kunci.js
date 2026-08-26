function checkDec(el){
 var ex = /^[0-9]+\.?[0-9]*$/;
 if(ex.test(el.value)==false){
   el.value = el.value.substring(0,el.value.length - 1);
  }
}
function checkNum(el){
 var ex = /^[0-9]+$/;
 if(ex.test(el.value)==false){
   el.value = el.value.substring(0,el.value.length - 1);
  }
}
function checkIP(el){
 var ex = /^([0-9]+)?\.?([0-9]+)?\.?([0-9]+)?\.?([0-9]+)?$/;
 if(ex.test(el.value)==false){
   el.value = el.value.substring(0,el.value.length - 1);
  }
}

function checkSlave(el){
 var ex = /^[0-9\.\;/]+$/;
 if(ex.test(el.value)==false){
   el.value = el.value.substring(0,el.value.length - 1);
  }
}

function checkIPList(el){
 var ex = /^[0-9\.\/\n]+$/;
 if(ex.test(el.value)==false){
   el.value = el.value.substring(0,el.value.length - 1);
  }
}
function checkCron1(el){
 var ex = /^\*?\/?([0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])?$/;
 if(ex.test(el.value)==false){
   el.value = el.value.substring(0,el.value.length - 1);
  }
}
function checkCron2(el){
 var ex = /^\*?\/?([0-9]|1[0-9]|2[0-3])?$/;
 if(ex.test(el.value)==false){
   el.value = el.value.substring(0,el.value.length - 1);
  }
}
function checkCron3(el){
 var ex = /^\*?\/?([0-9]|1[0-9]|2[0-9]|3[0-1])?$/;
 if(ex.test(el.value)==false){
   el.value = el.value.substring(0,el.value.length - 1);
  }
}
function checkCron4(el){
 var ex = /^\*?\/?(?:[0-9]|1[0-2])?$/;
 if(ex.test(el.value)==false){
   el.value = el.value.substring(0,el.value.length - 1);
  }
}
function checkCron5(el){
 var ex = /^(\*?\/?[0-6]?)$/;
 if(ex.test(el.value)==false){
   el.value = el.value.substring(0,el.value.length - 1);
  }
}
function checkDomain(el){
 var ex = /^[a-z0-9\.\-]+$/;
 if(ex.test(el.value)==false){
   el.value = el.value.substring(0,el.value.length - 1);
  }
}
function checkDomainList(el){
 var ex = /^[a-z0-9\.\-\n]+$/;
 if(ex.test(el.value)==false){
   el.value = el.value.substring(0,el.value.length - 1);
  }
}
function checkHostList(el){
 var ex = /^[a-z0-9\.\-\n ]+$/;
 if(ex.test(el.value)==false){
   el.value = el.value.substring(0,el.value.length - 1);
  }
}
function checkPortList(el){
 var ex = /^[0-9,]+$/;
 if(ex.test(el.value)==false){
   el.value = el.value.substring(0,el.value.length - 1);
  }
}
function checkSOA(el){
 var ex = /^[a-z0-9\.\-\ ]+$/;
 if(ex.test(el.value)==false){
   el.value = el.value.substring(0,el.value.length - 1);
  }
}

function checkFWList(el){
 var ex = /^[a-z0-9\.\-\n: ]+$/;
 if(ex.test(el.value)==false){
   el.value = el.value.substring(0,el.value.length - 1);
  }
}
function checkURL(el){
 var ex = /^[a-z0-9\.\-:\/]+$/;
 if(ex.test(el.value)==false){
   el.value = el.value.substring(0,el.value.length - 1);
  }
}

function checkString(el){
 var ex = /^[a-zA-Z0-9\.\-\_]+$/;
 if(ex.test(el.value)==false){
   el.value = el.value.substring(0,el.value.length - 1);
  }
}

function checkText(el){
 var ex = /^[a-zA-Z0-9\.\-\_\ ]+$/;
 if(ex.test(el.value)==false){
   el.value = el.value.substring(0,el.value.length - 1);
  }
}

function checkDomainList(el){
 var ex = /^[a-z0-9\.\-\n]+$/;
 if(ex.test(el.value)==false){
   el.value = el.value.substring(0,el.value.length - 1);
  }
}
