function persWithConst(pYear,pMonth,pDay,c){
  const epbase = pYear - (pYear >= 0 ? 474 : 473);
  const epyear = 474 + (epbase % 2820);
  const mdays = pMonth <= 7 ? (pMonth - 1) * 31 : ((pMonth - 1) * 30) + 6;
  const jdn = pDay + mdays + Math.floor((epyear * 682 - 110) / 2816) + (epyear - 1) * 365 + Math.floor(epbase / 2820) * 1029983 + (c);
  let j = jdn + 32044;
  let g = Math.floor(j / 146097);
  let dg = j % 146097;
  let cc = Math.floor((Math.floor(dg / 36524) + 1) * 3 / 4);
  let dc = dg - cc * 36524;
  let b = Math.floor(dc / 1461);
  let db = dc % 1461;
  let a = Math.floor((Math.floor(db / 365) + 1) * 3 / 4);
  let da = db - a * 365;
  let y = g * 400 + cc * 100 + b * 4 + a;
  let m = Math.floor((da * 5 + 308) / 153) - 2;
  let d = da - Math.floor((m + 4) * 153 / 5) + 122;
  let Y = y - 4800 + Math.floor((m + 2) / 12);
  let M = (m + 2) % 12 + 1;
  let D = d + 1;
  return `${Y.toString().padStart(4,'0')}-${M.toString().padStart(2,'0')}-${D.toString().padStart(2,'0')}`;
}
[1948319,1948320,1948321,1948322,1948323].forEach(c=>{
  console.log(c,'->',persWithConst(1404,1,1,c));
});
