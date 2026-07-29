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
    'Equivale a $15.000 por mes · ahorrás $36.000': 'Works out to $15,000/month · you save $36,000',
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
    // Doble factor
    'Doble factor por correo': 'Two-factor by email',
    'Con esto activado, además de tu contraseña te pedimos un código de 6 dígitos que te llega por correo cada vez que entrás. Es la mejor protección para tu cuenta.': 'With this on, besides your password we ask for a 6-digit code sent to your email every time you log in. It is the best protection for your account.',
    'Pedirme un código por correo al entrar': 'Ask me for an email code when logging in',
    '¿A qué correo te mandamos el código?': 'Which email should we send the code to?',
    'Código de 6 dígitos': '6-digit code', 'Entrar': 'Log in',
    'Enviarme un código nuevo': 'Send me a new code',
    'Te enviamos un código nuevo.': 'We sent you a new code.',
    'El código no es correcto.': 'That code is not correct.',
    'El código venció. Pedí uno nuevo.': 'The code expired. Request a new one.',
    'Demasiados intentos. Pedí un código nuevo.': 'Too many attempts. Request a new code.',
    'El código vence en 10 minutos': 'The code expires in 10 minutes',
    'Cancelar': 'Cancel',
    'Doble factor activado. La próxima vez que entres te vamos a pedir un código.': 'Two-factor enabled. Next time you log in we will ask for a code.',
    'Doble factor desactivado.': 'Two-factor disabled.',
    'El correo para el código no es válido.': 'The email for the code is not valid.',
    // Recuperar contraseña
    '¿Olvidaste tu contraseña?': 'Forgot your password?',
    'Recuperar contraseña': 'Reset your password',
    'Escribí tu email y te mandamos un enlace para crear una nueva.': 'Enter your email and we will send you a link to create a new one.',
    'Enviarme el enlace': 'Send me the link',
    '¿Te acordaste?': 'Remembered it?',
    'Revisá tu correo': 'Check your email',
    'El enlace vence en 2 horas.': 'The link expires in 2 hours.',
    'Volver a ingresar': 'Back to log in',
    'Escribí un email válido.': 'Enter a valid email.',
    'Demasiados intentos. Esperá 15 minutos y probá de nuevo.': 'Too many attempts. Wait 15 minutes and try again.',
    'Elegí tu contraseña nueva': 'Choose your new password',
    'Para la cuenta': 'For the account',
    'Contraseña nueva (mínimo 8 caracteres)': 'New password (at least 8 characters)',
    'Guardar contraseña': 'Save password',
    '¡Contraseña cambiada!': 'Password changed!',
    'Ya podés usar tu cuenta con la contraseña nueva.': 'You can now use your account with the new password.',
    'Te dejamos la sesión iniciada.': 'We kept you logged in.',
    'El enlace venció, ya se usó o está incompleto.': 'The link expired, was already used, or is incomplete.',
    'Los enlaces duran 2 horas y sirven una sola vez. Pedí uno nuevo.': 'Links last 2 hours and work only once. Request a new one.',
    'Pedir un enlace nuevo': 'Request a new link',
    'Las contraseñas no coinciden.': 'The passwords do not match.',
    'La contraseña debe tener al menos 8 caracteres.': 'The password must be at least 8 characters.',
    'Printika Pro Anual': 'Printika Pro Annual',
    '2 meses gratis': '2 months free',
    '2 meses sin cargo': '2 months at no cost',
    // Pantalla de acceso denegado (ui_pantalla_error)
    'Acceso solo para administradores.': 'Administrators only.',
    'Esta pantalla es del panel de administración del sitio. Con tu cuenta podés entrar a todo lo demás. Si creés que tendrías que ver esto, probá con la cuenta de administradora.':
      'This screen belongs to the site admin panel. Your account can reach everything else. If you think you should be seeing this, try the administrator account.',
    'Ir a la plataforma': 'Go to the platform',
    // Adelanto de las secciones con candado (comunidad/inc/vistazo.php)
    'Incluido en el plan completo': 'Included in the full plan',
    'Elegí abajo con qué plan lo desbloqueás.': 'Choose below which plan unlocks it.',
    'Ver los planes': 'See the plans',
    'Estás en el plan Gratuito: tenés la calculadora y los recursos. El plan completo suma la librería STL, todo Mi taller y el soporte por Telegram.':
      'You are on the Free plan: you have the calculator and the resources. The full plan adds the STL library, all of My Workshop and Telegram support.',
    'Para probar y empezar': 'To try it out and get started',
    'Calculadora de costos online': 'Online cost calculator',
    'Cálculo en ARS, USD y EUR': 'Pricing in ARS, USD and EUR',
    'Recursos en videos y PDF': 'Video and PDF resources',
    'Librería STL completa': 'Full STL library',
    'Soporte por Telegram': 'Telegram support',
    // Librería STL
    'Modelos listos para imprimir y vender, sin buscar por internet.': 'Models ready to print and sell, without hunting around the internet.',
    'Una biblioteca que crece todos los meses, con archivos probados en impresora antes de publicarlos. Los bajás y los imprimís, sin sorpresas.':
      'A library that grows every month, with files tested on a printer before we publish them. You download and print, no surprises.',
    'Modelos nuevos todos los meses': 'New models every month',
    'Probados antes de publicarse: no vas a perder filamento': 'Tested before publishing: you will not waste filament',
    'Descarga directa, sin registrarte en otro lado': 'Direct download, no signing up somewhere else',
    // Presupuestos
    'Presupuestos con tu logo, en PDF, en menos de un minuto.': 'Quotes with your logo, as a PDF, in under a minute.',
    'Elegís el cliente, cargás las piezas y sale el PDF armado con tus datos y tu marca. Se acabó el "te paso el precio por WhatsApp" a mano.':
      'Pick the client, add the pieces and out comes a PDF with your details and your brand. No more typing prices into WhatsApp by hand.',
    'PDF con tu logo y tus datos': 'PDF with your logo and your details',
    'Quedan todos guardados y los buscás cuando quieras': 'They all stay saved and you can search them anytime',
    'Del presupuesto a la venta, sin volver a cargar nada': 'From quote to sale without re-entering anything',
    // Productos
    'Tus piezas con su precio ya calculado, listas para presupuestar.': 'Your pieces with their price already worked out, ready to quote.',
    'Cargás una vez lo que imprimís seguido, con su costo real, y después lo usás en cualquier presupuesto con un clic.':
      'Enter what you print often once, with its real cost, then use it in any quote with one click.',
    'El precio se calcula solo con tu costo de filamento': 'The price works itself out from your filament cost',
    'Se reutilizan en presupuestos y ventas': 'Reused across quotes and sales',
    'Si cambia el filamento, actualizás en un solo lugar': 'If filament prices change, you update in one place',
    // Clientes
    'Toda tu clientela junta, con lo que le vendiste a cada uno.': 'All your clients in one place, with what you sold each of them.',
    'Dejás de buscar el teléfono en la agenda y el pedido viejo en el chat. Cada cliente con sus datos, sus presupuestos y sus compras.':
      'No more digging for a phone number in your contacts and an old order in a chat. Each client with their details, quotes and purchases.',
    'Datos, teléfono y dirección en un solo lugar': 'Details, phone and address in one place',
    'El historial de lo que le vendiste': 'The history of what you sold them',
    'Se completa solo al hacer un presupuesto': 'Fills itself in as you make a quote',
    // Stock
    'Stock de materiales': 'Materials stock',
    'Cuánto filamento te queda, y cuánto te costó de verdad.': 'How much filament you have left, and what it really cost you.',
    'Cargás cada rollo con lo que pagaste y el sistema descuenta lo que vas usando. Así el precio que cobrás sale de tu costo real, no de un cálculo de hace seis meses.':
      'Enter each spool with what you paid and the system subtracts what you use. That way your price comes from your real cost, not from a calculation you did six months ago.',
    'Cuánto queda de cada rollo': 'How much is left of each spool',
    'Aviso cuando se está por acabar': 'A heads-up when one is running out',
    'Tus costos reales alimentan la calculadora': 'Your real costs feed the calculator',
    // Ventas
    'Lo que vendiste, lo que cobraste y lo que te falta cobrar.': 'What you sold, what you got paid, and what is still owed.',
    'Cada venta con su cliente, su fecha y su estado de pago. Sin planilla aparte y sin acordarte de memoria quién te debe.':
      'Every sale with its client, date and payment status. No separate spreadsheet and no remembering from memory who owes you.',
    'Cobrado y pendiente, separado': 'Paid and outstanding, kept apart',
    'Nace del presupuesto: no cargás dos veces': 'Born from the quote: you never enter it twice',
    'Alimenta las estadísticas del taller': 'Feeds your workshop statistics',
    // Estadísticas
    'Si tu taller gana plata, y con qué la gana.': 'Whether your workshop makes money, and what makes it.',
    'Cuánto facturaste por mes, qué piezas dejan más margen y quiénes son tus mejores clientes. Es la diferencia entre imprimir y tener un negocio.':
      'How much you billed each month, which pieces leave the most margin and who your best clients are. It is the difference between printing and running a business.',
    'Facturación mes a mes': 'Month-by-month billing',
    'Qué te deja margen y qué no': 'What leaves you margin and what does not',
    'Tus mejores clientes, ordenados': 'Your best clients, ranked',
    // Configuración
    'Configuración del taller': 'Workshop settings',
    'Tu logo, tus datos y tu moneda en todo lo que sale a la calle.': 'Your logo, your details and your currency on everything that goes out.',
    'Los presupuestos salen con tu marca y no con la nuestra. También elegís la moneda y activás la verificación en dos pasos.':
      'Quotes go out with your brand, not ours. You also pick the currency and turn on two-step verification.',
    'Tu logo en los PDF': 'Your logo on the PDFs',
    'Moneda a elección (ARS, USD, EUR)': 'Currency of your choice (ARS, USD, EUR)',
    'Verificación en dos pasos': 'Two-step verification',
    // Telegram
    'Preguntá y te contestamos, sin formularios ni esperas de días.': 'Ask and we answer, no forms and no waiting days.',
    'El grupo privado donde están los que ya viven de esto: consultas de precios, de materiales y de máquina, con respuesta de gente que imprime todos los días.':
      'The private group where people already making a living from this hang out: questions about pricing, materials and machines, answered by people who print every day.',
    'Respuesta rápida a tus consultas': 'Quick answers to your questions',
    'Grupo privado sólo para suscriptores': 'Private group for subscribers only',
    'Novedades y herramientas nuevas primero': 'News and new tools first',
    // Verificación de correo
    'Confirmá tu correo': 'Confirm your email',
    'Hasta que confirmes, la cuenta queda en espera.': 'Until you confirm, your account stays on hold.',
    // El aviso de spam: va en toda pantalla que deja esperando un correo
    '¿No te llegó? Mirá en la carpeta de Correo no deseado o Spam: a veces cae ahí. Si lo encontrás, marcalo como «No es spam» y los próximos te van a llegar bien.':
      'Did not get it? Check your Spam or Junk folder — it sometimes lands there. If you find it, mark it as “Not spam” and the next ones will arrive fine.',
    'Reenviar el correo': 'Resend the email',
    'Te reenviamos el correo de confirmación.': 'We resent the confirmation email.',
    'Recién te enviamos un correo. Esperá un par de minutos antes de pedir otro.': 'We just sent you an email. Wait a couple of minutes before asking for another.',
    'No pudimos enviar el correo. Probá de nuevo en unos minutos.': 'We could not send the email. Try again in a few minutes.',
    'El envío de correos no está configurado.': 'Email sending is not set up.',
    '¡Correo confirmado!': 'Email confirmed!',
    'Ya podés usar la calculadora, la librería STL y los recursos.': 'You can now use the calculator, the STL library and the resources.',
    'Entrar a la plataforma': 'Enter the platform', 'Continuar con mi plan': 'Continue with my plan',
    'Ir a la plataforma': 'Go to the platform',
    'Tu correo ya estaba confirmado': 'Your email was already confirmed',
    'No hace falta hacer nada más.': 'Nothing else to do.',
    'El enlace venció': 'The link expired',
    'Los enlaces de confirmación duran 48 horas. Te mandamos uno nuevo y listo.': 'Confirmation links last 48 hours. We will send you a new one.',
    'Enviarme un enlace nuevo': 'Send me a new link',
    'Listo, te enviamos un enlace nuevo.': 'Done, we sent you a new link.',
    'Enlace no válido': 'Invalid link',
    'Puede que ya lo hayas usado o que el enlace esté incompleto.': 'You may have used it already, or the link is incomplete.',
    'Ingresá con tu cuenta y desde ahí podés pedir un correo nuevo.': 'Log in and you can request a new email from there.',
    'Demasiados intentos fallidos. Esperá 15 minutos y probá de nuevo.': 'Too many failed attempts. Wait 15 minutes and try again.',
    '¿En qué moneda trabajás?': 'Which currency do you work in?',
    'Todos tus presupuestos, productos y la calculadora del taller van a usar esta moneda. Podés cambiarla cuando quieras desde el chip de moneda.': 'All your quotes, products and the workshop calculator will use this currency. You can change it anytime from Settings.',
    'Peso argentino': 'Argentine peso', 'Dólar': 'US dollar', 'Euro': 'Euro', 'Guardando...': 'Saving...',
    'Completa el costo del formulario (y el precio, si está vacío).': 'It fills in the cost (and the price, if empty).',
    'Administrás la plataforma.': 'You manage the platform.',
    '/mes': '/month', '/año': '/year',
    'Ítem externo': 'External item', 'Detalle': 'Detail', 'Monto $': 'Amount $',
    'Pintado a mano, bulones, envío especial...': 'Hand painting, bolts, special shipping...'
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
