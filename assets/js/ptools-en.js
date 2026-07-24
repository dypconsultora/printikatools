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
    'Próximamente': 'Coming soon', 'Idioma': 'Language', 'Herramientas': 'Tools', 'Precios': 'Pricing', 'Inicio': 'Home', 'Tu plan': 'Your plan',
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
    'Guardar configuración': 'Save settings',
    // Stock
    'Llevá el control de tus rollos de filamento e insumos del taller.': 'Track your filament spools and workshop supplies.',
    'Todavía no tenés rollos cargados': 'No spools added yet',
    'Tocá "Agregar rollo" para sumar tu primer filamento. Después vas a poder descontar stock automáticamente al marcar un presupuesto como vendido.': 'Tap "Add spool" to load your first filament. Then stock will be deducted automatically when you mark a quote as sold.',
    'Todavía no tenés insumos cargados': 'No supplies added yet',
    'Tocá "Agregar insumo" para sumar boquillas, alcohol isopropílico, repuestos o cualquier material de tu taller y controlar cuánto te queda.': 'Tap "Add supply" to track nozzles, isopropyl alcohol, spare parts or any workshop material.',
    'Marca *': 'Brand *', 'Color *': 'Color *', 'Nombre *': 'Name *', 'Tipo (opcional)': 'Type (optional)',
    'Negro': 'Black', 'Blanco': 'White', 'Gris': 'Gray', 'Rojo': 'Red', 'Azul': 'Blue', 'Verde': 'Green',
    'Amarillo': 'Yellow', 'Naranja': 'Orange', 'Violeta': 'Purple', 'Rosa': 'Pink', 'Transparente': 'Clear',
    'Si el rollo es nuevo, los dos valores son iguales. Si ya lo empezaste a usar, cargá el peso original (cuando estaba lleno) y cuánto te queda hoy.': 'If the spool is new, both values are the same. If you already used it, enter the original weight (when full) and what you have left today.',
    'Peso original (g)': 'Original weight (g)', 'Peso disponible (g)': 'Available weight (g)',
    'Cuánto pesa el rollo lleno (normalmente 1 kg = 1000 g).': 'Weight of the full spool (usually 1 kg = 1000 g).',
    'Lo que te queda hoy. Si es nuevo, dejalo igual al peso original.': 'What you have left today. If new, keep it equal to the original.',
    'Costo por kilo (opcional)': 'Cost per kilo (optional)', 'Unidad': 'Unit',
    'unidades': 'units', 'litros': 'liters', 'metros': 'meters', 'gramos': 'grams', 'packs': 'packs',
    'Aviso de bajo stock (opcional)': 'Low stock alert (optional)',
    'Te avisamos cuando la cantidad quede en este número o menos.': 'We warn you when the quantity reaches this number or less.',
    'Negro, blanco, rojo...': 'Black, white, red...', 'Boquilla 0.4mm, alcohol isopropílico...': '0.4mm nozzle, isopropyl alcohol...',
    'Repuesto, limpieza...': 'Spare part, cleaning...',
    'Editar rollo': 'Edit spool', 'Editar insumo': 'Edit supply', 'Guardar cambios': 'Save changes',
    'Rollo agregado al stock.': 'Spool added to stock.', 'Rollo actualizado.': 'Spool updated.', 'Rollo eliminado.': 'Spool deleted.',
    'Insumo agregado al stock.': 'Supply added to stock.', 'Insumo actualizado.': 'Supply updated.', 'Insumo eliminado.': 'Supply deleted.',
    // Presupuestos (listado y editor)
    '+ Nuevo presupuesto': '+ New quote', 'Ordenar:': 'Sort:', 'Más recientes': 'Newest', 'Más antiguos': 'Oldest',
    'Mayor total': 'Highest total', 'Cliente': 'Client', 'Fecha': 'Date', 'Estado': 'Status', 'Abrir': 'Open',
    'Buscar por cliente...': 'Search by client...', 'Sin nombre': 'Untitled', 'Volver a pendiente': 'Back to pending',
    'No hay presupuestos que coincidan.': 'No quotes match your search.',
    'Todavía no creaste ningún presupuesto.': "You haven't created any quotes yet.",
    'Presupuesto guardado.': 'Quote saved.', 'Presupuesto marcado como vendido.': 'Quote marked as sold.',
    'Presupuesto vuelto a pendiente.': 'Quote set back to pending.', 'Presupuesto eliminado.': 'Quote deleted.',
    '&larr; Presupuestos': '← Quotes', 'Editar presupuesto': 'Edit quote',
    'Tu presupuesto está vacío. Elegí un producto guardado o calculá una pieza nueva con la calculadora.': 'Your quote is empty. Pick a saved product or calculate a new part with the calculator.',
    'Ganancia estimada (precio − costo)': 'Estimated profit (price − cost)',
    'Peso soporte (g)': 'Support weight (g)', 'Peso carrete soporte (g)': 'Support spool weight (g)',
    'Otro / Personalizado': 'Other / Custom',
    'Elegí tu modelo y autocompletamos el consumo (W). Si no está en la lista, dejá "Otro / Personalizado".': 'Pick your model and we autofill the wattage. If it is not listed, keep "Other / Custom".',
    'Consumo (W)': 'Power draw (W)', 'Tarifa ($/kWh)': 'Rate ($/kWh)', 'Costo impresora $': 'Printer cost $',
    'Vida útil (hs)': 'Lifespan (hrs)', 'Mantenimiento anual $': 'Yearly maintenance $',
    'Preparación (min)': 'Prep time (min)', 'Post-proceso (min)': 'Post-processing (min)',
    'Tarifa mano de obra ($/h)': 'Labor rate ($/h)', 'Empaquetado $': 'Packaging $', 'Envío $': 'Shipping $',
    'Otros costos $': 'Other costs $', 'Tasa de fallos (%)': 'Failure rate (%)', 'Ganancia (%)': 'Profit (%)',
    'Electricidad': 'Electricity', 'Desgaste de máquina': 'Machine wear', 'Mano de obra': 'Labor',
    'Extras y fallos': 'Extras & failures', 'Costo total': 'Total cost', 'Precio sugerido': 'Suggested price',
    'Precio final $ (editable)': 'Final price $ (editable)',
    'Guardar también como producto del catálogo': 'Also save as a catalog product',
    '+ Agregar pieza': '+ Add part', 'Notas': 'Notes', 'Unidades': 'Units',
    // Productos
    'Tu catálogo de piezas: cargalas acá o guardalas desde la calculadora de un presupuesto.': 'Your parts catalog: add them here or save them from a quote calculator.',
    '+ Nuevo producto': '+ New product',
    'Costo = lo que te sale imprimirlo · Precio = lo que cobrás. Si no sabés el costo, usá la calculadora de al lado.': 'Cost = what it takes to print it · Price = what you charge. Use the side calculator if unsure.',
    'Costo $': 'Cost $', 'Precio $': 'Price $', 'Cancelar edición': 'Cancel editing',
    'Calculá cuánto te sale la pieza y cargalo en el formulario.': 'Work out what the part costs you and load it into the form.',
    'Cargá el primero con el formulario de arriba, o guardá una pieza desde la calculadora de un presupuesto.': 'Add your first one with the form above, or save a part from a quote calculator.',
    'Producto': 'Product', 'Descripción': 'Description', 'Costo': 'Cost', 'Precio': 'Price',
    'Buscar producto...': 'Search products...', 'Producto actualizado.': 'Product updated.',
    'Producto eliminado.': 'Product deleted.', 'Editar producto': 'Edit product', 'Crear producto': 'Create product',
    // Clientes
    'Tu cartera de clientes. Al crear un presupuesto podés elegirlos y quedan vinculados.': 'Your client list. When creating a quote you can pick them and they stay linked.',
    '+ Crear cliente': '+ Create client',
    'Este cliente se creó desde un presupuesto: completá el email y el teléfono.': 'This client was created from a quote: fill in their email and phone.',
    'Nombre y apellido': 'Full name', 'Nombre empresa': 'Company name', 'Contacto': 'Contact',
    'Ubicación': 'Location', 'Presup.': 'Quotes', 'Datos incompletos': 'Missing details',
    '+ Presupuesto': '+ Quote', 'Buscar por nombre, email o empresa...': 'Search by name, email or company...',
    'Cliente actualizado.': 'Client updated.', 'Cliente eliminado. Sus presupuestos se conservan.': 'Client deleted. Their quotes are kept.',
    'Editar cliente': 'Edit client', 'Nuevo cliente': 'New client', 'No hay clientes que coincidan.': 'No clients match your search.',
    // Ventas / Estadísticas
    'Ingresos por ventas y gastos del taller, mes a mes.': 'Sales income and workshop expenses, month by month.',
    'Agregar gasto': 'Add expense', 'Agregar ingreso': 'Add income', 'Concepto': 'Concept', 'Monto': 'Amount',
    'Agregar': 'Add', 'Sin movimientos este mes': 'No entries this month',
    'Cargá tus ingresos (ventas presenciales, MercadoLibre, etc.) o gastos del mes.': 'Add your income (in-person sales, MercadoLibre, etc.) or expenses for the month.',
    'Los ingresos de presupuestos vendidos aparecen acá automáticamente.': 'Income from sold quotes shows up here automatically.',
    '· automático': '· automatic', 'Ver presupuesto': 'View quote',
    'Venta presencial, filamento, envío...': 'In-person sale, filament, shipping...',
    'Ingreso agregado.': 'Income added.', 'Gasto agregado.': 'Expense added.', 'Movimiento eliminado.': 'Entry deleted.',
    'Exportar CSV': 'Export CSV', 'Mes': 'Month', 'Origen': 'Source', 'Presupuesto vendido': 'Sold quote', 'Manual': 'Manual',
    'Total ingresos': 'Total income', 'Total gastos': 'Total expenses',
    'Últimos 6 meses': 'Last 6 months', 'Ingresos': 'Income', 'Gastos': 'Expenses',
    'Enero': 'January', 'Febrero': 'February', 'Marzo': 'March', 'Abril': 'April', 'Mayo': 'May', 'Junio': 'June',
    'Julio': 'July', 'Agosto': 'August', 'Septiembre': 'September', 'Octubre': 'October',
    'Noviembre': 'November', 'Diciembre': 'December',
    // Configuración / Tu plan / varios
    'El nombre y teléfono del taller aparecen al pie de tus PDF.': 'Your workshop name and phone appear at the bottom of your PDFs.',
    'PNG o JPG (máx. 2 MB). Si no cargás ninguno, usamos el de Printika Tools.': 'PNG or JPG (max 2 MB). If you upload none, we use the Printika Tools logo.',
    'Sin logo propio': 'No custom logo', 'Quitar mi logo y volver al de Printika Tools': 'Remove my logo and go back to the Printika Tools one',
    'Configuración guardada.': 'Settings saved.', 'Ingresá tu nombre.': 'Enter your name.',
    'Sos administrador': 'You are an administrator', 'Sos administrador: acceso completo.': 'You are an administrator: full access.',
    'Si completaste el pago, tu plan se activa en unos instantes. Actualizá la página en un ratito.': 'If you completed the payment, your plan activates in moments. Refresh the page shortly.',
    'El pago online se está configurando. Escribinos por Telegram y lo activamos a mano.': 'Online payments are being set up. Message us on Telegram and we will activate it manually.',
    'No pudimos iniciar el pago. Probá de nuevo en unos minutos o escribinos.': 'We could not start the payment. Try again in a few minutes or message us.',
    'El pago se procesa de forma segura en Mercado Pago y la renovación es automática. ¿Dudas? Escribinos por': 'Payments are processed securely by Mercado Pago and renew automatically. Questions? Message us on',
    'Estamos preparando la plataforma. ¡Muy pronto!': 'We are getting the platform ready. Very soon!',
    'Mostrar contraseña': 'Show password', 'Ocultar contraseña': 'Hide password',
    'Mensual': 'Monthly', 'Anual': 'Yearly', 'Moneda': 'Currency',
    '¿En qué moneda trabajás?': 'Which currency do you work in?',
    'Todos tus presupuestos, productos y la calculadora del taller van a usar esta moneda. Podés cambiarla cuando quieras desde el chip de moneda.': 'All your quotes, products and the workshop calculator will use this currency. You can change it anytime from Settings.',
    'Peso argentino': 'Argentine peso', 'Dólar': 'US dollar', 'Euro': 'Euro', 'Guardando...': 'Saving...',
    'Completa el costo del formulario (y el precio, si está vacío).': 'It fills in the cost (and the price, if empty).',
    'Administrás la plataforma.': 'You manage the platform.',
    '/mes': '/month', '/año': '/year'
  };

  function traducirNodo(n) {
    if (n.nodeType === 3) {
      var t = n.nodeValue, r = t.replace(/\s+/g, ' ').trim();
      if (r && D[r]) { n.nodeValue = ' ' + D[r] + ' '; return; }
      if (r.indexOf('Hola, ') === 0) n.nodeValue = t.replace('Hola, ', 'Hi, ');
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
