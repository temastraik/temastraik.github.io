function calculator_rubbish() {
    const rubbish_all = parseInt(document.getElementById('rubbish_all').value) || 0;
    const rubbish_plastic = parseInt(document.getElementById('rubbish_plastic').value) || 0;
    const rubbish_paper = parseInt(document.getElementById('rubbish_paper').value) || 0;
    const rubbish_glass = parseInt(document.getElementById('rubbish_glass').value) || 0;
    const rubbish_metal = parseInt(document.getElementById('rubbish_metal').value) || 0;
    
    const rubbish_percent =  (rubbish_metal+rubbish_glass+rubbish_paper+rubbish_plastic)/rubbish_all*100 || 0;
    const conversion_plastic = rubbish_plastic/40;
    const conversion_paper = rubbish_paper/50;
    const conversion_glass = rubbish_glass/50;
    const conversion_metal = rubbish_metal/50;

    document.getElementById('result_rubbish_percent').innerHTML = `<strong>Ваш процент переработанных отходов составляет:</strong> ${rubbish_percent.toFixed(0)}%`;
    document.getElementById('result_conversion_plastic').innerHTML = `<strong>Вы сэкономили:</strong> ${conversion_plastic.toFixed(2)}м³ нефти`;
    document.getElementById('result_conversion_paper').innerHTML = `<strong>Вы сэкономили:</strong> ${conversion_paper.toFixed(2)}м³ древесины`;
    document.getElementById('result_conversion_glass').innerHTML = `<strong>Вы сэкономили:</strong> ${conversion_glass.toFixed(2)}м³ песка`;
    document.getElementById('result_conversion_metal').innerHTML = `<strong>Вы сэкономили:</strong> ${conversion_metal.toFixed(2)}м³ руды`;
}

function calculate_CO2() {
    const km_all = parseFloat(document.getElementById('km_all').value) || 0;
    const kol_trips = parseFloat(document.getElementById('kol_trips').value) || 0;
    const electric_power = parseFloat(document.getElementById('electric_power').value) || 0;
    const meat_kg = parseFloat(document.getElementById('meat_kg').value) || 0;
    const volume_all = parseFloat(document.getElementById('volume_all').value) || 0;
    
    const road_CO2 = km_all * 0.231;
    const trips_CO2 = kol_trips * 0.5;
    const energy_CO2 = electric_power * 0.5;
    const meat_CO2 = meat_kg * 4.5;
    const waste_CO2 = volume_all * 0.5;
    const CO2_kg = road_CO2 + trips_CO2 + energy_CO2 + meat_CO2 + waste_CO2;
    
    document.getElementById('result_CO2').innerHTML = `<strong>Ваш общий углеродный след составляет:</strong> ${CO2_kg.toFixed(1)}кг в месяц`;
}

function calculator_water() {
    const water_shower = parseFloat(document.getElementById('water_shower').value) || 0;
    const water_bath = parseFloat(document.getElementById('water_bath').value) || 0;
    const kol_dishes = parseFloat(document.getElementById('kol_dishes').value) || 0;
    const kol_dishwasher = parseFloat(document.getElementById('kol_dishwasher').value) || 0;
    const kol_water_machine = parseFloat(document.getElementById('kol_water_machine').value) || 0;
    
    const litres_shower = water_shower * 12;
    const litres_bath = water_bath * 80;
    const litres_dishes = kol_dishes * 15;
    const litres_dishwater = kol_dishwasher * 10;
    const litres_machine = kol_water_machine * 50;
    const liters_water = kol_dishes + kol_water_machine + kol_dishwasher + water_bath + water_shower;
    
    document.getElementById('result_water').innerHTML = `<strong>Ваш расход воды составляет:</strong> ${liters_water.toFixed(1)}литров в неделю`;
}