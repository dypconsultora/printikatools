// Traducción ES -> EN de la plataforma (pantallas de usuarios).
// Se activa cuando ptIdioma() === 'en'. Traduce textos y atributos por
// coincidencia exacta y sigue traduciendo lo que el JS redibuja.
(function () {
  if (typeof ptIdioma !== 'function' || ptIdioma() !== 'en') return;

  var D = {
    // Menú y estructura
    'Plataforma': 'Platform', 'Mi taller': 'My workshop', 'Soporte': 'Support',
    'Calculadora': 'Calculator', 'Librería STL': 'STL Library', 'Recursos': 'Resources',
    'Presupuestos': 'Quotes', 'Productos': 'Products', 'Clientes': 'Clients',
    'Stock Materiales': 'Materials Stock', 'Ventas': 'Sales', 'Estadísticas': 'Statistics',
    'Configuración': 'Settings', 'Día': 'Day', 'Noche': 'Night',
    'Suscriptor': 'Subscriber', 'Administrador': 'Administrator',
    'Próximamente': 'Coming soon', 'Idioma': 'Language', 'Inicio': 'Home', 'Tu plan': 'Your plan',
    'Disponible en el plan completo': 'Available in the full plan',
    // Login / registro
    'Comunidad': 'Community', 'Ingresá con tu cuenta de miembro': 'Log in with your member account',
    'Contraseña': 'Password', 'Ingresar': 'Sign in', '¿No tenés cuenta?': "Don't have an account?",
    'Registrate': 'Sign up', 'Email o contraseña incorrectos.': 'Wrong email or password.',
    'Crear cuenta': 'Create account', 'Sumate a la comunidad de impresión 3D': 'Join the 3D printing community',
    'Nombre': 'Name', 'Contraseña (mínimo 8 caracteres)': 'Password (at least 8 characters)',
    'Repetir contraseña': 'Repeat password', '¿Ya tenés cuenta?': 'Already have an account?',
    'Ingresá': 'Log in', 'cambiar': 'change', 'Cerrar sesión': 'Log out',
    // Tablero
    'Calculadora de costos': 'Cost calculator',
    'Calculá el precio justo de tus impresiones 3D.': 'Work out the right price for your 3D prints.',
    'Modelos listos para imprimir, seleccionados por Printika.': 'Print-ready models, curated by Printika.',
    'Generá y enviá presupuestos profesionales a tus clientes.': 'Create and send professional quotes to your clients.',
    'Tu catálogo de piezas con costo y precio de venta.': 'Your parts catalog with cost and sale price.',
    'Tu cartera de clientes, vinculada a los presupuestos.': 'Your client list, linked to your quotes.',
    'Ingresos y gastos del taller, mes a mes.': 'Workshop income and expenses, month by month.',
    'Ganancia, ingresos y gastos de los últimos meses.': 'Profit, income and expenses over recent months.',
    'Controlá tus rollos de filamento e insumos.': 'Track your filament spools and supplies.',
    'Estás en el plan Gratuito.': "You're on the Free plan.",
    'Pasate al plan completo': 'Upgrade to the full plan',
    'para desbloquear todo Mi taller.': 'to unlock the whole workshop.',
    // Tu plan
    'Estás en el plan Gratuito: calculadora y librería STL. Pasate al plan completo para desbloquear todo Mi taller.':
      "You're on the Free plan: calculator and STL library. Upgrade to unlock the whole workshop.",
    'Gratuito': 'Free', 'Para siempre': 'Forever',
    'Calculadora de costos completa': 'Full cost calculator',
    'Comunidad Mensual': 'Monthly Community', 'Comunidad Anual': 'Yearly Community',
    'Renovación mes a mes, sin permanencia': 'Renews monthly, cancel anytime',
    'Todo Mi taller: presupuestos, productos, clientes': 'Full workshop: quotes, products, clients',
    'Stock, ventas y estadísticas': 'Stock, sales and statistics',
    'Tus datos guardados en tu cuenta': 'Your data saved in your account',
    'Todo lo del plan mensual': 'Everything in the monthly plan',
    'Más de 2 meses gratis': '2+ months free', 'Más de 2 meses sin cargo': '2+ months at no cost',
    'Precio congelado por 12 meses': 'Price locked for 12 months',
    'Equivale a $14.167 por mes · ahorrás $46.000': 'Works out to $14,167/month · you save $46,000',
    'Tu plan actual': 'Your current plan', 'Incluido en tu plan': 'Included in your plan',
    'Suscribirme con Mercado Pago': 'Subscribe with Mercado Pago',
    'El pago se procesa de forma segura en Mercado Pago y la renovación es automática.': 'Payments are processed securely by Mercado Pago and renew automatically.',
    '¿Dudas? Escribinos por': 'Questions? Message us on',
    // Librería / Recursos
    'Modelos listos para imprimir, seleccionados por Printika Tools.': 'Print-ready models, curated by Printika Tools.',
    'Todos': 'All', 'Descargar': 'Download', 'descarga': 'download', 'descargas': 'downloads',
    'Estamos preparando la librería': "We're getting the library ready",
    'Muy pronto vas a encontrar acá modelos STL listos para imprimir.': 'Very soon you will find print-ready STL models here.',
    'Guías en PDF y videos para mejorar tus impresiones y tu negocio 3D.': 'PDF guides and videos to improve your prints and your 3D business.',
    'Todavía no hay PDFs cargados': 'No PDFs uploaded yet',
    'Muy pronto vas a encontrar acá guías y material descargable.': 'Very soon you will find guides and downloads here.',
    'Todavía no hay videos cargados': 'No videos uploaded yet',
    'Muy pronto vas a encontrar acá videos y tutoriales sobre impresión 3D.': 'Very soon you will find 3D printing videos and tutorials here.',
    'Suscriptores': 'Subscribers', 'Cerrar': 'Close',
    // Presupuestos (listado)
    'Generá presupuestos y llevá el control de lo vendido.': 'Create quotes and keep track of what you sold.',
    'Pendientes': 'Pending', 'Vendidos': 'Sold', 'Nuevo presupuesto': 'New quote',
    'Buscar por cliente o pieza...': 'Search by client or part...',
    'Marcar vendido': 'Mark as sold', 'Vendido': 'Sold', 'Pendiente': 'Pending',
    // Editor de presupuesto
    'Completá los datos del cliente y agregá las piezas.': 'Fill in the client details and add the parts.',
    'Para quién es': 'Who is it for', 'Nombre del cliente *': 'Client name *',
    'Escribí el nombre (nuevo o de tu cartera)...': 'Type the name (new or from your list)...',
    'No hace falta que exista: si es nuevo, se crea solo al guardar.': "It doesn't need to exist: new clients are created on save.",
    'Gestionar clientes': 'Manage clients', 'Notas (opcional)': 'Notes (optional)',
    'Seña, plazo de entrega, aclaraciones...': 'Deposit, delivery time, remarks...',
    'Piezas': 'Parts', 'Elegir un producto': 'Pick a product', 'Subtotal': 'Subtotal',
    'Descuento': 'Discount', 'Total': 'Total', 'Ganancia': 'Profit',
    'Guardar': 'Save', 'Guardar y marcar vendido': 'Save and mark sold',
    'Compartir': 'Share', 'Descargar PDF': 'Download PDF', 'Imprimir': 'Print',
    'Calcular nueva pieza': 'Calculate new part', 'Nombre de la pieza *': 'Part name *',
    'Soporte GoPro, llavero personalizado...': 'GoPro mount, custom keychain...',
    'Descripción (opcional)': 'Description (optional)', 'Color, material, acabado...': 'Color, material, finish...',
    'Material': 'Material', 'Peso usado (g)': 'Weight used (g)', 'Precio carrete $': 'Spool price $',
    'Peso carrete (g)': 'Spool weight (g)', 'Horas': 'Hours', 'Minutos': 'Minutes',
    'Material de soporte': 'Support material', 'Impresora y electricidad': 'Printer & electricity',
    'Mano de obra y extras': 'Labor & extras', 'Margen de ganancia': 'Profit margin',
    'Modelo de impresora': 'Printer model', 'Costo de la pieza': 'Part cost',
    'Precio final': 'Final price', 'Agregar al presupuesto': 'Add to quote',
    'Guardar como producto en mi catálogo': 'Save as a product in my catalog',
    'Completá el nombre del cliente para guardar.': 'Enter the client name to save.',
    'Agregá al menos una pieza.': 'Add at least one part.',
    'Cant.': 'Qty', 'Precio unit.': 'Unit price', 'Pieza': 'Part',
    // Productos / Clientes
    'Tu catálogo de piezas: costo, precio y ganancia de cada una.': 'Your parts catalog: cost, price and profit for each one.',
    'Nuevo producto': 'New product', 'Cargar producto': 'Add product',
    'Tu cartera de clientes y su historial de presupuestos.': 'Your clients and their quote history.',
    'Crear cliente': 'Create client', 'Teléfono': 'Phone', 'Empresa': 'Company',
    'Dirección': 'Address', 'Ciudad': 'City', 'Provincia': 'State/Province',
    // Stock
    'Llevá el control de tus rollos de filamento e insumos del taller.': 'Track your filament spools and workshop supplies.',
    'Filamentos': 'Filaments', 'Otros materiales': 'Other supplies',
    'Cargá tus rollos de filamento.': 'Add your filament spools.',
    'Agregar rollo': 'Add spool', 'Agregar insumo': 'Add supply',
    'Todavía no tenés rollos cargados': 'No spools added yet',
    'Anotá los repuestos e insumos de tu taller.': 'Keep track of your spare parts and supplies.',
    'Marca': 'Brand', 'Tipo': 'Type', 'Color': 'Color', 'Disponible': 'Available',
    'Costo por kilo': 'Cost per kilo', 'Acciones': 'Actions', 'Queda poco': 'Running low',
    'Agotado': 'Out of stock', 'Bajo stock': 'Low stock', 'Cantidad': 'Quantity',
    'Nuevo rollo': 'New spool', 'Nuevo insumo': 'New supply', 'Cancelar': 'Cancel',
    'Editar': 'Edit', 'Eliminar': 'Delete', 'Insumo': 'Supply', 'Rollo': 'Spool',
    // Ventas / Estadísticas / Configuración
    'Ingresos y gastos de tu taller, mes a mes.': 'Your workshop income and expenses, month by month.',
    'Ingresos': 'Income', 'Gastos': 'Expenses', 'Movimiento': 'Entry', 'Movimientos': 'Entries',
    'Agregar movimiento': 'Add entry', 'Ingreso': 'Income', 'Gasto': 'Expense',
    'Cómo viene tu taller: ingresos, gastos y ganancia, mes a mes.': 'How your workshop is doing: income, expenses and profit, month by month.',
    'Últimos 6 meses': 'Last 6 months', 'Mes': 'Month',
    'Los datos de tu taller: aparecen en los PDF de tus presupuestos.': 'Your workshop details: they appear on your quote PDFs.',
    'Datos del taller': 'Workshop details', 'Tu nombre *': 'Your name *',
    'Nombre del taller / negocio': 'Workshop / business name', 'Teléfono / WhatsApp': 'Phone / WhatsApp',
    'Moneda del taller': 'Workshop currency', 'Logo para tus PDF': 'Logo for your PDFs',
    'Guardar configuración': 'Save settings'
  };

  function traducirNodo(n) {
    if (n.nodeType === 3) {
      var t = n.nodeValue, r = t.trim();
      if (r && D[r]) n.nodeValue = t.replace(r, D[r]);
      return;
    }
    if (n.nodeType !== 1 || n.tagName === 'SCRIPT' || n.tagName === 'STYLE') return;
    ['placeholder', 'title', 'aria-label'].forEach(function (a) {
      var v = n.getAttribute && n.getAttribute(a);
      if (v && D[v.trim()]) n.setAttribute(a, D[v.trim()]);
    });
    for (var i = 0; i < n.childNodes.length; i++) traducirNodo(n.childNodes[i]);
  }

  function traducir(raiz) { try { traducirNodo(raiz); } catch (e) {} }

  document.documentElement.lang = 'en';
  var arrancar = function () {
    traducir(document.body);
    new MutationObserver(function (ms) {
      ms.forEach(function (m) {
        m.addedNodes && m.addedNodes.forEach(function (n) { traducir(n); });
      });
    }).observe(document.body, { childList: true, subtree: true });
  };
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', arrancar);
  } else {
    arrancar();
  }
})();
