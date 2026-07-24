// Traducción ES -> EN de la landing. Usa la misma preferencia que la
// plataforma (localStorage ptools_idioma) con detección del navegador.
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
    'Idioma':'Language','Herramientas':'Tools','Comunidad':'Community','Precios':'Pricing','FAQ':'FAQ',
    'Calculadora':'Calculator','Iniciar sesión':'Log in','Registrarse':'Sign up',
    'Comunidad 3D en español':'3D community, built for makers',
    'Manejá tu taller de impresión 3D':'Run your 3D printing workshop','como un negocio':'like a business',
    'Calculadora de costos, presupuestos, clientes y stock de materiales. Las herramientas de una comunidad de makers, en un mismo lugar.':'Cost calculator, quotes, clients and material stock. The tools of a maker community, all in one place.',
    'Comenzar gratis':'Start for free','Ver planes':'See plans',
    'materiales soportados':'supported materials','monedas · ARS USD EUR':'currencies · ARS USD EUR',
    'herramientas en camino':'tools on the way',
    '¿Qué necesita tu taller?':'What does your workshop need?',
    'Construidas junto a la comunidad, pensadas para makers y emprendedores 3D.':'Built with the community, designed for makers and 3D entrepreneurs.',
    'Calculadora de costos':'Cost calculator','Disponible':'Available',
    'Material, tiempo de máquina, desgaste, electricidad, mano de obra y ganancia. El precio justo de cada impresión, en tres monedas.':'Material, machine time, wear, electricity, labor and profit. The right price for every print, in three currencies.',
    'Material (PLA, 86 g)':'Material (PLA, 86 g)','Máquina (5 h 20 m)':'Machine (5 h 20 m)',
    'Mano de obra + ganancia':'Labor + profit','Precio final sugerido':'Suggested final price',
    'Presupuestos':'Quotes','Pronto':'Soon',
    'Generá presupuestos profesionales, guardalos y marcá los vendidos.':'Create professional quotes, save them and mark them sold.',
    'Clientes':'Clients','Tu cartera de clientes con su historial de trabajos.':'Your client list with their job history.',
    'Stock':'Stock','Rollos e insumos controlados, con descuento automático al vender.':'Spools and supplies under control, auto-deducted when you sell.',
    'Librería STL':'STL Library','Modelos listos para imprimir, exclusivos para suscriptores.':'Print-ready models, exclusive to subscribers.',
    'Estadísticas':'Statistics','Cuánto imprimís, vendés y ganás, mes a mes.':'How much you print, sell and earn, month by month.',
    'Probar la calculadora':'Try the calculator',
    'Todo lo que necesitás, en un mismo lugar':'Everything you need, in one place',
    'Ser parte es más que usar herramientas.':'Being part of it is more than using tools.',
    'Comunidad activa':'Active community','Soporte directo':'Direct support',
    'Te ayudamos por WhatsApp cuando lo necesitás.':'We help you over WhatsApp whenever you need it.',
    'Comunidad de makers':'Maker community','Precios, consejos y experiencia compartida.':'Prices, tips and shared experience.',
    'Tus datos en tu cuenta':'Your data in your account','Accesibles desde cualquier dispositivo.':'Available from any device.',
    'Mejoras constantes':'Constant improvements','Herramientas nuevas todos los meses.':'New tools every month.',
    'Contenido exclusivo':'Exclusive content','Archivos y recursos para suscriptores.':'Files and resources for subscribers.',
    'Sin permanencia':'No lock-in','Entrás y salís cuando quieras.':'Join and leave whenever you want.',
    'Planes simples, sin sorpresas':'Simple plans, no surprises',
    'Empezá gratis y pasate a la suscripción cuando tu taller lo pida.':'Start for free and upgrade when your workshop asks for it.',
    'Gratuito':'Free','Para probar y empezar':'To try it out and get started',
    'Calculadora de costos online':'Online cost calculator','Cálculo en ARS, USD y EUR':'Pricing in ARS, USD and EUR',
    'Cuenta gratuita en la comunidad':'Free community account','Herramientas del taller':'Workshop tools',
    'Datos guardados en tu cuenta':'Data saved in your account','Empezar gratis':'Start for free',
    'Comunidad Mensual':'Monthly Community','/mes':'/month',
    'Renovación mes a mes, sin permanencia':'Renews monthly, cancel anytime',
    'Calculadora completa (versión PRO)':'Full calculator (PRO version)',
    'Mi Taller: presupuestos, clientes y stock':'My Workshop: quotes, clients and stock',
    'Librería STL y estadísticas':'STL library and statistics',
    'Tus datos guardados en tu cuenta':'Your data saved in your account',
    'Soporte técnico prioritario':'Priority tech support','Herramientas nuevas cada mes':'New tools every month',
    'Suscribirme':'Subscribe','Más de 2 meses gratis':'2+ months free',
    'Comunidad Anual':'Yearly Community','/año':'/year',
    'Equivale a $14.167 por mes · ahorrás $46.000':'Works out to $14,167/month · you save $46,000',
    'Un solo pago y te olvidás todo el año':'One payment and you are set for the year',
    'Todo lo del plan mensual':'Everything in the monthly plan',
    'Más de 2 meses sin cargo ($46.000 de ahorro)':'2+ months at no cost ($46,000 in savings)',
    'Precio congelado por 12 meses':'Price locked for 12 months',
    'Acceso anticipado a herramientas nuevas':'Early access to new tools',
    'Preguntas frecuentes':'Frequently asked questions',
    '¿Cómo me uno a la comunidad?':'How do I join the community?',
    'Creás tu cuenta gratis con el botón "Registrarse" y después activás tu suscripción escribiéndonos por WhatsApp. En minutos tenés acceso a todas las herramientas.':'Create your free account with the "Sign up" button and then activate your subscription by messaging us. You get access to every tool in minutes.',
    '¿El pago es mensual o anual?':'Is billing monthly or yearly?',
    'Como prefieras: el plan mensual cuesta $18.000 y se renueva mes a mes sin permanencia, y el plan anual cuesta $170.000 — ahorrás $46.000 (más de 2 meses gratis) y el precio queda congelado todo el año.':'Whichever you prefer: the monthly plan costs $18,000 and renews month to month with no lock-in, and the yearly plan costs $170,000 — you save $46,000 (2+ months free) and the price stays locked all year.',
    '¿Puedo cancelar cuando quiera?':'Can I cancel anytime?',
    'Sí. Si cancelás, mantenés el acceso hasta el vencimiento de tu suscripción y no se te cobra nada más.':'Yes. If you cancel, you keep access until your subscription expires and you are never charged again.',
    '¿Qué incluye el plan gratuito?':'What does the free plan include?',
    'La calculadora de costos online completa, sin necesidad de registrarte, y una cuenta gratuita para conocer la plataforma por dentro.':'The full online cost calculator, no sign-up required, plus a free account to see the platform from the inside.',
    '¿Mis datos quedan guardados?':'Is my data saved?',
    'Sí. Cada suscriptor tiene su propia cuenta: tus presupuestos, clientes y stock se guardan y podés consultarlos desde cualquier dispositivo.':'Yes. Every subscriber has their own account: your quotes, clients and stock are stored and available from any device.',
    '¿Van a agregar más herramientas?':'Will you add more tools?',
    'Todos los meses sumamos mejoras y herramientas nuevas: presupuestos, clientes, stock, librería STL y estadísticas son las próximas en llegar.':'We ship improvements and new tools every month: quotes, clients, stock, STL library and statistics are next in line.',
    'Empezá hoy — es gratis':'Start today — it is free',
    'Creá tu cuenta, probá la calculadora y descubrí por qué cada vez más makers manejan su taller con Printika Tools.':'Create your account, try the calculator and see why more and more makers run their workshop with Printika Tools.',
    'Crear mi cuenta':'Create my account',
    'Las herramientas y la comunidad para manejar tu taller de impresión 3D como un negocio.':'The tools and the community to run your 3D printing workshop like a business.',
    'Plataforma':'Platform','© Printika Tools. Todos los derechos reservados.':'© Printika Tools. All rights reserved.',
    'Comunidad 3D':'3D Community'
  };
  function tr(n){
    if(n.nodeType===3){var t=n.nodeValue,r=t.replace(/\s+/g,' ').trim();if(r&&D[r])n.nodeValue=' '+D[r]+' ';return;}
    if(n.nodeType!==1||n.tagName==='SCRIPT'||n.tagName==='STYLE')return;
    ['placeholder','title','aria-label'].forEach(function(a){var v=n.getAttribute&&n.getAttribute(a);
      if(v&&D[v.trim()])n.setAttribute(a,D[v.trim()]);});
    for(var i=0;i<n.childNodes.length;i++)tr(n.childNodes[i]);
  }
  document.documentElement.lang='en';
  document.addEventListener('DOMContentLoaded',function(){try{tr(document.body);}catch(e){}});
})();
