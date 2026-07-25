// Traducción ES -> EN del cotizador público. Misma preferencia que el
// resto del sitio (localStorage ptools_idioma) con detección del navegador.
function ptIdioma(){try{var v=localStorage.getItem('ptools_idioma');if(v==='es'||v==='en')return v;}catch(e){}
  return ((navigator.language||'es').toLowerCase().indexOf('es')===0)?'es':'en';}
function ptIdiomaSet(v){try{localStorage.setItem('ptools_idioma',v);}catch(e){}location.reload();}
document.addEventListener('DOMContentLoaded',function(){
  document.querySelectorAll('.idioma button').forEach(function(b){
    b.classList.toggle('activo',b.dataset.idi===ptIdioma());
    b.addEventListener('click',function(){ptIdiomaSet(b.dataset.idi);});
  });
});
(function () {
  if (ptIdioma() !== 'en') return;
  var D = {
    'IDIOMA':'LANGUAGE','Idioma':'Language',
    'Calculadora de Costos 3D':'3D Cost Calculator',
    'Calcula el precio justo para tus impresiones 3D':'Work out the right price for your 3D prints',
    'Ir a la web →':'Go to the website →','Modo':'Mode','Salir':'Log out',
    'Versión PRO habilitada por tiempo limitado · se deshabilita el':'PRO version enabled for a limited time · turns off on',
    'Proyecto':'Project','Producto':'Product','Nombre del proyecto...':'Project name...',
    'Nombre del producto o pieza...':'Product or part name...',
    'Configuracion de Material':'Material Setup','Tipo de material':'Material type',
    'Policarbonato (PC)':'Polycarbonate (PC)','Nylon + Fibra de Carbono':'Nylon + Carbon Fiber',
    'PLA Madera':'Wood PLA','Flexible':'Flexible',
    'Peso usado':'Weight used','Precio del carrete':'Spool price','Peso del carrete':'Spool weight',
    'Costo por gramo':'Cost per gram','Costo total material':'Total material cost',
    'Incluir material de soporte':'Include support material','Peso soporte':'Support weight',
    'Material soporte':'Support material','Mismo material':'Same material',
    'PVA (soluble)':'PVA (soluble)','HIPS (soluble)':'HIPS (soluble)',
    'Precio carrete soporte':'Support spool price','Peso carrete soporte':'Support spool weight',
    'Tiempo de Impresion':'Print Time','Horas':'Hours','Minutos':'Minutes',
    'Tiempo total de impresion':'Total print time',
    'Costos de Electricidad':'Electricity Costs','Modelo de impresora':'Printer model',
    'Otro / Personalizado':'Other / Custom',
    'Elegí tu modelo y autocompletamos el consumo (W). Si no está en la lista, dejá "Otro / Personalizado".':'Pick your model and we autofill the wattage. If it is not listed, keep "Other / Custom".',
    'Consumo':'Power draw','(Watts)':'(Watts)','Tarifa electrica':'Electricity rate',
    'Costo electrico total':'Total electricity cost',
    'Costos de Mano de Obra':'Labor Costs','Preparacion':'Prep time','Post-proceso':'Post-processing',
    'Tarifa por hora':'Hourly rate','Costo mano de obra':'Labor cost',
    'Depreciacion de la Maquina':'Machine Depreciation','Costo impresora':'Printer cost',
    'Vida util':'Lifespan','(horas)':'(hours)','Mantenimiento anual':'Yearly maintenance',
    'Depreciacion por hora':'Depreciation per hour','Costo depreciacion total':'Total depreciation cost',
    'Costos Adicionales':'Additional Costs','Empaquetado':'Packaging','Envio':'Shipping',
    'Tasa de fallos':'Failure rate','Otros costos fijos':'Other fixed costs',
    'Comisiones Mercado Libre':'Mercado Libre Fees','Vender por Mercado Libre':'Sell on Mercado Libre',
    'Tipo de publicacion':'Listing type','Clasica':'Classic','Premium':'Premium',
    'Comision ML':'ML commission','Cargo fijo por unidad':'Fixed fee per unit',
    'Comision estimada sobre precio final':'Estimated commission on final price',
    'Comisiones ML Argentina (Ene 2026): Clasica 11,8%-17,1% | Premium 12%-17% segun categoria. Cargos fijos: hasta $15.000 = $1.095/u | $15k-$25k = $2.190/u | $25k-$33k = $2.628/u.':'ML Argentina fees (Jan 2026): Classic 11.8%-17.1% | Premium 12%-17% by category. Fixed fees: up to $15,000 = $1,095/u | $15k-$25k = $2,190/u | $25k-$33k = $2,628/u.',
    'Verifica siempre en tu cuenta de vendedor.':'Always double-check in your seller account.',
    'Margen de Ganancia':'Profit Margin','Usar precio fijo':'Use fixed price','Ganancia:':'Profit:',
    'Precio fijo':'Fixed price','Margen resultante':'Resulting margin',
    'Resumen de Costos':'Cost Summary','Costo de material':'Material cost',
    'Material de soporte':'Support material','Costo electrico':'Electricity cost',
    'Mano de obra':'Labor','Depreciacion maquina':'Machine depreciation',
    'Costos adicionales':'Additional costs','Subtotal (costo)':'Subtotal (cost)',
    '+ Margen de ganancia':'+ Profit margin','Precio Final':'Final Price',
    'Mercado Libre':'Mercado Libre','+ Comision sobre precio':'+ Commission on price',
    '+ Cargo fijo ML':'+ ML fixed fee','Total comisiones ML':'Total ML fees',
    'Publicar en Mercado Libre a':'List on Mercado Libre at',
    'Despues de comisiones te queda':'After fees you keep','(tu precio final)':'(your final price)',
    'Tu ganancia:':'Your profit:','Costo por hora':'Cost per hour','Costo por gramo':'Cost per gram',
    'Distribucion de costos':'Cost breakdown','Acciones':'Actions',
    'Guardar como producto':'Save as product','Crear presupuesto':'Create quote',
    'Guardar Cotizacion':'Save Quote','Exportar PDF':'Export PDF','Compartir':'Share','Reiniciar':'Reset',
    'Cotizaciones Guardadas':'Saved Quotes','No hay cotizaciones guardadas':'No saved quotes',
    'No se pudieron cargar las cotizaciones':'Could not load your quotes',
    'Función exclusiva de la versión PRO':'PRO-only feature',
    'Para habilitar la versión PRO contactanos para habilitar la suscripción.':'To enable the PRO version, contact us to activate your subscription.',
    'Contactar por WhatsApp':'Contact us on WhatsApp','Cerrar':'Close',
    'NOVEDADES':'NEWS','¡No te pierdas lo que viene!':"Don't miss what's coming!",
    'Para enterarte de nuevas herramientas y novedades dejanos tu email.':'Leave your email to hear about new tools and updates.',
    'Ingresá tu email':'Enter your email','Quiero enterarme':'Keep me posted','Ahora no':'Not now',
    'Presupuesto':'Quote','Material':'Material','Peso de la pieza':'Part weight',
    'Tiempo de impresion':'Print time','Margen de ganancia':'Profit margin','Precio final':'Final price',
    'Cotizacion guardada correctamente':'Quote saved successfully','Cotizacion cargada':'Quote loaded',
    'Cotizacion eliminada':'Quote deleted','Resumen copiado al portapapeles':'Summary copied to clipboard',
    'Valores reiniciados':'Values reset','Ingresa un email valido':'Enter a valid email',
    'Gracias! Te vamos a avisar de las novedades.':'Thanks! We will keep you posted.',
    'Escribi el nombre del producto en el campo de arriba':'Type the product name in the field above',
    'Producto actualizado en tu catalogo':'Product updated in your catalog',
    'Producto creado en tu catalogo':'Product created in your catalog'
  };
  function tr(n){
    if(n.nodeType===3){
      var t=n.nodeValue,r=t.replace(/\s+/g,' ').trim();
      if(!r)return;
      if(D[r]){n.nodeValue=' '+D[r]+' ';return;}
      // Botones con emoji adelante ("💾 Guardar Cotizacion")
      var m=r.match(/^([^A-Za-zÁÉÍÓÚáéíóúñ¡¿]+)\s*(.+)$/);
      if(m&&D[m[2]])n.nodeValue=' '+m[1]+' '+D[m[2]]+' ';
      return;
    }
    if(n.nodeType!==1||n.tagName==='SCRIPT'||n.tagName==='STYLE')return;
    ['placeholder','title','aria-label'].forEach(function(a){var v=n.getAttribute&&n.getAttribute(a);
      if(v&&D[v.trim()])n.setAttribute(a,D[v.trim()]);});
    for(var i=0;i<n.childNodes.length;i++)tr(n.childNodes[i]);
  }
  document.documentElement.lang='en';
  var go=function(){
    try{tr(document.body);}catch(e){}
    document.title='3D Printing Cost Calculator';
    try{
      new MutationObserver(function(ms){ms.forEach(function(m){
        m.addedNodes&&m.addedNodes.forEach(function(n){try{tr(n);}catch(e){}});
      });}).observe(document.body,{childList:true,subtree:true});
    }catch(e){}
  };
  if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',go);}else{go();}
})();
