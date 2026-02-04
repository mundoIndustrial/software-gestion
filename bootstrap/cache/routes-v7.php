<?php

app('router')->setCompiledRoutes(
    array (
  'compiled' => 
  array (
    0 => false,
    1 => 
    array (
      '/api/asistencia-personal/obtener-todas-las-personas' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::ABZYS4cixL6o2jWi',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/api/v1/ordenes' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.v1.ordenes.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'api.v1.ordenes.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/prendas' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'prendas.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'prendas.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/pedidos' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'pedidos.crear',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/cotizaciones' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'cotizaciones.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'cotizaciones.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/procesos/tipos' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'procesos.tipos',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/epp/imagenes/upload' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'epp.imagenes.upload',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/epp' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'epp.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'epp.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/epps/buscar' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'epp.buscar',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/epp-debug' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'epp.debug',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/epp/categorias/all' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'epp.categorias',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/pedidos-editable/items/agregar' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'pedidos-editable.agregar-item',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/pedidos-editable/items/eliminar' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'pedidos-editable.eliminar-item',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/pedidos-editable/items' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'pedidos-editable.obtener-items',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/pedidos-editable/validar' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'pedidos-editable.validar',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/pedidos-editable/crear' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'pedidos-editable.crear',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/pedidos-editable/subir-imagenes' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'pedidos-editable.subir-imagenes',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/pedidos-editable/render-item-card' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'pedidos-editable.render-item-card',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/personal/list' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'personal.list',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/horarios/list' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'horarios.list',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/horarios/roles-disponibles' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'horarios.roles-disponibles',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/horarios' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'horarios.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/asistencias/obtener' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asistencias.obtener',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/asistencias/dia' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asistencias.dia',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/asistencias/rellenar-inteligente' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asistencias.rellenar-inteligente',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/asistencias/guardar' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asistencias.guardar',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/asistencias/mes' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asistencias.mes',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/articulos/guardar' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::QSObNR7of3xTjSNk',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/articulos' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::KCwD7aNjCRYkvIqQ',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/test-image' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::lOixkeDn6HvwIDEE',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/cartera/pedidos' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.cartera.list',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/up' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::gnAFfshis4Up2SoD',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::0J0EUPNDnts0Sa0g',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/test-echo' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'test.echo',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/test-pdf-upload' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'test.pdf-upload',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/dashboard' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'dashboard',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/profile' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'profile.edit',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'profile.update',
          ),
          1 => NULL,
          2 => 
          array (
            'PATCH' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'profile.destroy',
          ),
          1 => NULL,
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/refresh-csrf' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'refresh.csrf',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/asesores/fotos/eliminar' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'fotos.eliminar-inmediatamente',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/notifications' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'notifications.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/notifications/unread-count' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'notifications.unread-count',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/notifications/mark-multiple-read' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'notifications.mark-multiple-read',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/notifications/mark-all-read' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'notifications.mark-all-read',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/notifications/mark-read-on-open' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'notifications.mark-read-on-open',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/contador/notifications/marcar-leidas' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'contador.notifications.mark-all-read',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/contador/notifications' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'contador.notifications',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/asesores/notifications/mark-all-read' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.notifications.mark-all-read',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/asesores/notifications' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.notifications',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/supervisor-pedidos/notifications/mark-all-read' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'supervisor-pedidos.notifications.mark-all-read',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/insumos/notifications/marcar-leidas' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'insumos.notifications.mark-all-read',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/users' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'users.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'users.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/dashboard/entregas-costura-data' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'dashboard.entregas-costura-data',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/dashboard/entregas-corte-data' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'dashboard.entregas-corte-data',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/dashboard/kpis' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'dashboard.kpis',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/dashboard/recent-orders' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'dashboard.recent-orders',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/dashboard/news' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'dashboard.news',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/dashboard/admin-notifications' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'dashboard.admin-notifications',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/dashboard/news/mark-all-read' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'dashboard.news.mark-all-read',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/dashboard/audit-stats' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'dashboard.audit-stats',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/registros' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'registros.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'registros.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/registros/next-pedido' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'registros.next-pedido',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/registros/filter-options' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'registros.filter-options',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/registros/filter-orders' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'registros.filter-orders',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/registros/search' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'registros.search',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/registros/dias-batch' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.registros.dias-batch',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/bodega' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'bodega.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'bodega.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/bodega/search' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'bodega.search',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/bodega/next-pedido' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'bodega.next-pedido',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/configuracion' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'configuracion.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/configuracion/create-database' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'configuracion.createDatabase',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/configuracion/select-database' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'configuracion.selectDatabase',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/configuracion/migrate-users' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'configuracion.migrateUsers',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/configuracion/backup-database' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'configuracion.backupDatabase',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/configuracion/download-backup' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'configuracion.downloadBackup',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/configuracion/upload-google-drive' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'configuracion.uploadGoogleDrive',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/tableros' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'tableros.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'tableros.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/tableros/fullscreen' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'tableros.fullscreen',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/tableros/corte-fullscreen' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'tableros.corte-fullscreen',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/piso-corte' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'piso-corte.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/get-tiempo-ciclo' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'get-tiempo-ciclo',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/store-tela' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'store-tela',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/search-telas' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'search-telas',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/store-maquina' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'store-maquina',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/search-maquinas' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'search-maquinas',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/search-operarios' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'search-operarios',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/store-operario' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'store-operario',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/find-or-create-operario' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'find-or-create-operario',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/find-or-create-maquina' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'find-or-create-maquina',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/find-or-create-tela' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'find-or-create-tela',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/find-hora-id' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'find-hora-id',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/tableros/corte/dashboard' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'tableros.corte.dashboard',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/vistas' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'vistas.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/vistas/search' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.vistas.search',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/vistas/update-cell' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.vistas.update-cell',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/vistas/control-calidad' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'vistas.control-calidad',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/vistas/control-calidad-fullscreen' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'vistas.control-calidad-fullscreen',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/balanceo' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'balanceo.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/balanceo/prenda/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'balanceo.prenda.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/balanceo/prenda' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'balanceo.prenda.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/cotizaciones-prenda/crear' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'cotizaciones-prenda.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/cotizaciones-prenda' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'cotizaciones-prenda.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'cotizaciones-prenda.lista',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/cotizaciones-bordado/crear' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'cotizaciones-bordado.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/cotizaciones-bordado' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'cotizaciones-bordado.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'cotizaciones-bordado.lista',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/test-tela-prenda/crear' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'test-telas.crear',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/test-tela-prenda/listar' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'test-telas.listar',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/test-tela-prenda/limpiar' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'test-telas.limpiar',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/cotizaciones/pendientes' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'cotizaciones.pendientes',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/pendientes-count' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'cotizaciones.pendientes-count',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/contador/dashboard' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'contador.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/contador/todas' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'contador.todas',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/contador/por-revisar' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'contador.por-revisar',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/contador/aprobadas' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'contador.aprobadas',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/contador/costos/guardar' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'contador.costos.guardar',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/contador/tallas-costos' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'contador.tallas-costos.guardar',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/contador/cotizaciones-pendientes-count' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'contador.cotizaciones-pendientes-count',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/contador/perfil' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'contador.profile',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/contador/perfil/update' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'contador.profile.update',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/operario/dashboard' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'operario.dashboard',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/operario/mis-pedidos' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'operario.mis-pedidos',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/operario/api/pedidos' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'operario.api.pedidos',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/operario/buscar' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'operario.buscar',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/operario/reportar-pendiente' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'operario.reportar-pendiente',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/operario/debug' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'operario.debug',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/asesores/realtime/pedidos' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'realtime.pedidos.listar',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/asesores/dashboard' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.dashboard',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/asesores/dashboard-data' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.dashboard-data',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/asesores/perfil' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.profile',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/asesores/perfil/update' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.profile.update',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/asesores/pedidos' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.pedidos.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/asesores/cotizaciones/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.pedidos.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/asesores/pedidos/next-pedido' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.next-pedido',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/asesores/pedidos/api/listar' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.pedidos.api.listar',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/asesores/cotizaciones' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.cotizaciones.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.cotizaciones.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/asesores/cotizaciones/filtros/valores' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.cotizaciones.filtros.valores',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/asesores/pedidos-produccion' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.pedidos-produccion.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/asesores/api/pedidos' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.api.pedidos.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/asesores/clientes' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.clientes.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.clientes.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/asesores/reportes' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.reportes.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.reportes.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/asesores/prendas/agregar' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.prendas.agregar',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/asesores/cotizaciones/prenda/crear' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.cotizaciones-prenda.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/asesores/cotizaciones/prenda/lista' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.cotizaciones-prenda.lista',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/asesores/cotizaciones/bordado/crear' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.cotizaciones-bordado.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/asesores/cotizaciones/bordado/lista' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.cotizaciones-bordado.lista',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/asesores/api/tipos-broche-boton' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.api.tipos-broche-boton',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/asesores/api/tipos-manga' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.api.tipos-manga',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.api.tipos-manga.create',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/asesores/api/telas' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.api.telas',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.api.telas.create',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/asesores/api/colores' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.api.colores',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.api.colores.create',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/asesores/api/prendas/autocomplete' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.api.prendas.autocomplete',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/tallas-disponibles' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.tallas.disponibles',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/logo-cotizacion-tecnicas/tipos-disponibles' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.logo-cotizacion-tecnicas.tipos',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/logo-cotizacion-tecnicas/agregar' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.logo-cotizacion-tecnicas.agregar',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/supervisor-asesores/dashboard' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'supervisor-asesores.dashboard',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/supervisor-asesores/dashboard-stats' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'supervisor-asesores.dashboard-stats',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/supervisor-asesores/cotizaciones' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'supervisor-asesores.cotizaciones.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/supervisor-asesores/cotizaciones/data' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'supervisor-asesores.cotizaciones.data',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/supervisor-asesores/cotizaciones/filtros/valores' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'supervisor-asesores.cotizaciones.filtros.valores',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/supervisor-asesores/pedidos' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'supervisor-asesores.pedidos.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/supervisor-asesores/pedidos/data' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'supervisor-asesores.pedidos.data',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/supervisor-asesores/asesores' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'supervisor-asesores.asesores.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/supervisor-asesores/asesores/data' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'supervisor-asesores.asesores.data',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/supervisor-asesores/reportes' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'supervisor-asesores.reportes.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/supervisor-asesores/reportes/data' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'supervisor-asesores.reportes.data',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/supervisor-asesores/perfil' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'supervisor-asesores.profile.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/supervisor-asesores/perfil/stats' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'supervisor-asesores.profile.stats',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/supervisor-asesores/perfil/password-update' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'supervisor-asesores.profile.password-update',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/visualizador-logo/dashboard' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'visualizador-logo.dashboard',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/visualizador-logo/cotizaciones' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'visualizador-logo.cotizaciones',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/visualizador-logo/estadisticas' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'visualizador-logo.estadisticas',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/debug/registros/performance' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'debug.registros-performance',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/debug/registros/queries' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'debug.registros-queries',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/debug/registros/table-analysis' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'debug.registros-table-analysis',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/debug/registros/suggest-indices' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'debug.registros-suggest-indices',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/inventario-telas' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'inventario-telas.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/inventario-telas/store' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'inventario-telas.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/inventario-telas/ajustar-stock' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'inventario-telas.ajustar-stock',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/tipos-prenda' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.tipos-prenda',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/prenda/reconocer' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.prenda.reconocer',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/insumos/dashboard' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'insumos.dashboard',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/insumos/materiales' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'insumos.materiales.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/insumos/test' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'insumos.test',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/insumos/metrajes' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'insumos.metrajes.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/supervisor-pedidos' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'supervisor-pedidos.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/supervisor-pedidos/perfil/editar' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'supervisor-pedidos.profile',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/supervisor-pedidos/perfil/actualizar' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'supervisor-pedidos.update-profile',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/supervisor-pedidos/notificaciones' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'supervisor-pedidos.notifications',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/supervisor-pedidos/notificaciones/marcar-todas-leidas' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'supervisor-pedidos.mark-all-read',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/supervisor-pedidos/ordenes-pendientes-count' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'supervisor-pedidos.ordenes-pendientes-count',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/bordado' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'bordado.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/bordado/cotizaciones' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'bordado.cotizaciones',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/bordado/cotizaciones/lista' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'bordado.cotizaciones.lista',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/bordado/cotizaciones/medidas' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'bordado.cotizaciones.medidas',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/festivos' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.festivos.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/festivos/detailed' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.festivos.detailed',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/festivos/check' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.festivos.check',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/festivos/range' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.festivos.range',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/asistencia-personal' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asistencia-personal.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'asistencia-personal.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/asistencia-personal/crear' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asistencia-personal.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/asesores/pedidos-editable/crear' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.pedidos-editable.',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.pedidos-editable.crear',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/asesores/pedidos-editable/crear-desde-cotizacion' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.pedidos-editable.crear-desde-cotizacion',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/asesores/pedidos-editable/crear-nuevo' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.pedidos-editable.crear-nuevo',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/asesores/pedidos-editable/items/agregar' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.pedidos-editable.agregar-item',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/asesores/pedidos-editable/items/eliminar' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.pedidos-editable.eliminar-item',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/asesores/pedidos-editable/items' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.pedidos-editable.obtener-items',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/asesores/pedidos-editable/validar' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.pedidos-editable.validar',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/cartera/pedidos' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'cartera.pedidos',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/cartera/opciones-filtro' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.cartera.opciones-filtro',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/register' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'register',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::8fKjH9LXDwbZlTxO',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/login' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'login',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::F8iaegPMBaURfYyK',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/auth/google' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'auth.google',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/auth/google/callback' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'auth.google.callback',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/forgot-password' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'password.request',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'password.email',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/reset-password' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'password.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/verify-email' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'verification.notice',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/email/verification-notification' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'verification.send',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/confirm-password' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'password.confirm',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::Ne2NyYSjUEXizfSO',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/password' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'password.update',
          ),
          1 => NULL,
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/logout' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'logout',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'logout.get',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/despacho' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'despacho.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/pedidos-test' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::SC02IF8yEwjqJ27h',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/pedidos-temp' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::fzwyhSq62myL0qxK',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/gestion-bodega' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'gestion-bodega.',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/gestion-bodega/pedidos' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'gestion-bodega.pedidos',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/gestion-bodega/dashboard' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'gestion-bodega.dashboard',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/gestion-bodega/pedidos/observaciones' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'gestion-bodega.actualizar-observaciones',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/gestion-bodega/pedidos/fecha' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'gestion-bodega.actualizar-fecha',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/gestion-bodega/pedidos/export' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'gestion-bodega.export',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/broadcasting/auth' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::vo7HxcGuo97AUlJl',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'POST' => 1,
            'HEAD' => 2,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
    ),
    2 => 
    array (
      0 => '{^(?|/a(?|pi/(?|a(?|pi/(?|v1/ordenes/(?|([^/]++)(*:50)|cliente/([^/]++)(*:73)|estado/([^/]++)(*:95)|([^/]++)(?|/(?|aprobar(*:124)|iniciar\\-produccion(*:151)|completar(*:168))|(*:177)))|procesos/([^/]++)/activar\\-recibo(*:220))|rticulos/([^/]++)(*:246))|p(?|r(?|enda(?|s(?|/(?|([^/]++)(*:286)|search(*:300)|([^/]++)(?|(*:319)))|\\-pedido/([^/]++)/(?|e(?|ditar(?|(*:362)|/(?|campos(*:380)|tallas(*:394)))|stado(*:409))|procesos/([^/]++)(*:435)|variantes/([^/]++)/(?|e(?|ditar(?|(*:477)|/campos(*:492))|stado(*:506))|colores(*:522)|telas(*:535))))|\\-pedido/([^/]++)/(?|tallas(*:573)|variantes(*:590)|colores\\-telas(*:612)))|ocesos/(?|prendas/([^/]++)(?|(*:651))|([^/]++)(?|(*:671)|/(?|a(?|probar(*:693)|ctivar\\-recibo(*:715))|rechazar(*:732)|imagenes(?|(*:751)|/([^/]++)(?|/principal(*:781)|(*:789)))|e(?|ditar(*:808)|liminar(*:823))))))|e(?|didos/(?|([^/]++)(*:857)|cliente/([^/]++)(*:881)|([^/]++)/(?|c(?|onfirmar(*:913)|ancelar(*:928))|actualizar\\-descripcion(*:960)|epp(?|s(?|(*:978)|/(?|([^/]++)(?|(*:1001))|exportar/json(*:1024)))|(*:1035)|/(?|agregar(*:1055)|([^/]++)(?|(*:1075)|(*:1084))))))|rsonal/([^/]++)/rol(*:1117)))|c(?|otizaciones/([^/]++)(?|(*:1155)|/prendas/([^/]++)/telas\\-cotizacion(*:1199))|artera/pedidos/([^/]++)/(?|aprobar(*:1243)|rechazar(*:1260)|factura\\-datos(*:1283)))|epp/(?|([^/]++)/imagenes(*:1318)|imagenes/([^/]++)(*:1344)|([^/]++)(*:1361))|o(?|perario/pedido/([^/]++)(*:1398)|rdenes/([^/]++)/(?|procesos(*:1434)|novedades(?|(*:1455)|/add(*:1468))))|horarios/([^/]++)(*:1497)|registros(?|/([^/]++)/(?|dias(*:1535)|calcular\\-fecha\\-estimada(*:1569))|\\-por\\-orden(?|/([^/]++)(*:1603)|\\-bodega/([^/]++)(*:1629)))|logo\\-(?|pedidos/([^/]++)(*:1665)|cotizacion\\-tecnicas/(?|cotizacion/([^/]++)(*:1717)|([^/]++)(?|(*:1737)|/observaciones(*:1760))|prendas(?|(*:1780))))|tabla\\-original(?|/([^/]++)/procesos(*:1828)|\\-bodega/([^/]++)/procesos(*:1863))|bodega/([^/]++)/(?|dias(*:1896)|novedades(?|(*:1917)|/add(*:1930)))|valor\\-hora\\-extra/(?|([^/]++)(*:1971)|guardar(*:1987)))|s(?|esores/(?|notifications/([^/]++)/mark\\-read(*:2045)|p(?|edidos(?|/(?|([^/]++)(?|(*:2082)|/edit(*:2096)|(*:2105))|([0-9]+)/factura\\-datos(*:2138)|([0-9]+)/anular(*:2162)|([0-9]+)/confirmar\\-correccion(*:2201)|([0-9]+)/actualizar\\-prenda(*:2237))|\\-produccion/(?|([^/]++)(*:2271)|obtener\\-(?|datos\\-cotizacion/([^/]++)(*:2318)|prenda\\-completa/([0-9]+)/([0-9]+)(*:2361))|([0-9]+)/prenda/([0-9]+)/datos(*:2401)))|rendas\\-pedido/([0-9]+)/fotos(*:2441))|c(?|otizacion(?|es/(?|([^/]++)(*:2481)|imagenes/(?|prenda/([^/]++)(*:2517)|tela/([^/]++)(*:2539)|logo/([^/]++)(*:2561))|([^/]++)(?|/(?|ver(*:2589)|editar(*:2604)|imagenes(*:2621))|(*:2631))|guardar(*:2648)|([^/]++)(?|/(?|editar\\-borrador(*:2688)|borrador(*:2705)|anular(*:2720))|(*:2730))|prenda(?|(*:2749)|/([^/]++)(?|/e(?|ditar(*:2780)|nviar(*:2794))|(*:2804)))|bordado(?|(*:2825)|/([^/]++)(?|/e(?|ditar(*:2856)|nviar(*:2870))|(*:2880))))|/([^/]++)/pdf(?|/(?|prenda(*:2918)|combinada(*:2936)|logo(*:2949))|(*:2959)))|lientes/([^/]++)(?|(*:2989)))|api/pedidos/(?|([^/]++)(?|(*:3026)|/(?|estado(*:3045)|prendas(*:3061))|(*:3071))|filtro/estado(*:3094)|buscar/([^/]++)(*:3118)|([^/]++)/prendas(*:3143))|reportes/([^/]++)(?|(*:3173)))|istencia\\-personal/(?|([^/]++)(?|(*:3217)|/editar(*:3233)|(*:3242))|procesar\\-pdf(*:3265)|validar\\-registros(*:3292)|guardar\\-(?|registros(*:3322)|asistencia\\-detallada(*:3352)|hora\\-extra\\-agregada(*:3382)|marcas\\-(?|editadas(*:3410)|multiples(*:3428)))|calcular\\-horas(*:3454)|reportes/([^/]++)/(?|detalles(*:3492)|ausencias(*:3510))|agregar\\-marca\\-faltante(*:3544)|obtener\\-(?|todas\\-las\\-personas\\-test(*:3591)|horas\\-extras\\-agregadas(?|\\-batch(*:3634)|/([^/]++)(*:3652)))|test\\-simple(*:3675))))|/s(?|torage(?|/(?|cotizaciones/(.*)(*:3722)|p(?|rendas/(.*)(*:3746)|edidos/(.*)(*:3766))|(.*)(*:3780))|\\-serve/(.*)(*:3802))|upervisor\\-(?|asesores/(?|pedidos/([^/]++)/confirmar\\-correccion(*:3876)|asesores/([^/]++)(*:3902))|pedidos/(?|([^/]++)/(?|datos(*:3940)|factura\\-datos(*:3963)|comparar(*:3980))|notificaciones/([^/]++)/marcar\\-leida(*:4027)|filtro\\-opciones/([^/]++)(*:4061)|([^/]++)(?|(*:4081)|/(?|pdf(*:4097)|a(?|nular(*:4115)|probar(*:4130)|ctualizar(?|(*:4151)))|e(?|stado(*:4171)|ditar(*:4185))))|imagen/([^/]++)/([^/]++)(*:4221))))|/p(?|edidos(?|\\-public/(?|([0-9]+)/factura\\-datos(*:4282)|([0-9]+)/recibos\\-datos(*:4314))|/([^/]++)/(?|aprobar\\-supervisor(*:4356)|historial(*:4374)|seguimiento(*:4394)))|r(?|enda\\-variaciones/([^/]++)(*:4435)|ocesos/([^/]++)/activar\\-recibo(*:4475)))|/notifications/([^/]++)/mark\\-read(*:4520)|/users/([^/]++)(?|(*:4547)|/password(*:4565)|(*:4574))|/entrega/(pedido|bodega)(?|(*:4611)|/(?|co(?|stura\\-data(*:4640)|rte\\-data(*:4658))|order\\-data/([^/]++)(*:4688)|garments/([^/]++)(*:4714)|sizes/([^/]++)/([^/]++)(*:4746)|(costura|corte)/([^/]++)(?|(*:4782)))|(*:4793))|/re(?|gistros/(?|filter\\-column\\-options/([^/]++)(*:4852)|([^/]++)(?|/(?|recibos\\-datos(*:4890)|images(*:4905)|descripcion\\-prendas(*:4934))|(*:4944))|validate\\-pedido(*:4970)|update\\-(?|pedido(*:4996)|descripcion\\-prendas(*:5025))|([^/]++)(?|(*:5046))|update\\-status(*:5070)|([^/]++)/e(?|ntregas(*:5099)|dit\\-full(*:5117)))|set\\-password/([^/]++)(*:5150))|/o(?|rders/([^/]++)(*:5179)|perario/(?|pedido/([^/]++)(*:5214)|api/(?|pedido/([^/]++)(*:5245)|novedades/([^/]++)(*:5272)|completar\\-proceso/([^/]++)(*:5308))))|/facturas/([^/]++)(?|(*:5341)|/(?|preview(*:5361)|download(*:5378)))|/b(?|odega/(?|([^/]++)(?|(*:5414)|/(?|prendas(*:5434)|e(?|ntregas(*:5454)|dit\\-full(*:5472))))|validate\\-pedido(*:5500)|update\\-(?|pedido(*:5526)|descripcion\\-prendas(*:5555))|([^/]++)(*:5573))|alanceo/(?|prenda/([^/]++)(?|/(?|edit(*:5620)|balanceo(*:5637))|(*:5647))|([^/]++)(?|(*:5668)|/operacion(*:5687))|operacion/([^/]++)(?|(*:5718))|([^/]++)/(?|data(*:5744)|toggle\\-estado(*:5767))))|/tableros/(?|([^/]++)(?|(*:5803)|/duplicate(*:5822))|dashboard\\-tables\\-data(*:5855)|get\\-seguimiento\\-data(*:5886)|unique\\-values(*:5909))|/co(?|tizacion(?|es(?|\\-(?|prenda/([^/]++)(?|/e(?|ditar(*:5974)|nviar(*:5988))|(*:5998))|bordado/([^/]++)(?|/(?|borra(?|dor(*:6042)|r\\-imagen(*:6060))|e(?|ditar(*:6079)|nviar(*:6093)))|(*:6104)))|/([^/]++)/(?|borrar\\-imagen\\-(?|prenda(*:6153)|tela(*:6166))|logo/telas\\-prenda(?|(*:6197)|/([^/]++)(*:6215))|datos(*:6230)|costos(*:6245)|enviar(*:6260)|aprobar\\-(?|contador(*:6289)|para\\-pedido(*:6310)|aprobador(*:6328))|rechazar(*:6346)|historial(*:6364)|seguimiento(*:6384)))|/([^/]++)/pdf(*:6408))|ntador/(?|co(?|tizacion/([^/]++)(?|(*:6453)|/(?|costos(?|(*:6475)|(*:6484))|pdf(*:6497)|estado(*:6512))|(*:6522))|stos/obtener/([^/]++)(*:6553))|prenda/([^/]++)/(?|notas\\-tallas(*:6595)|texto\\-personalizado\\-tallas(*:6632))))|/v(?|isualizador\\-logo/cotizaciones/([^/]++)(?|(*:6691)|/pdf\\-logo(*:6710))|erify\\-email/([^/]++)/([^/]++)(*:6750))|/in(?|ventario\\-telas/(?|([^/]++)(*:6793)|historial(*:6811))|sumos/(?|materiales/([^/]++)/(?|guardar(?|(*:6863)|\\-ancho\\-metraje(?|(*:6891)|\\-prenda(*:6908)))|eliminar(*:6927)|obtener\\-(?|ancho\\-metraje(?|(*:6965)|\\-prenda/([^/]++)(*:6991))|prendas(*:7008))|cambiar\\-estado(*:7033))|api/(?|materiales/([^/]++)(*:7069)|filtros/([^/]++)(*:7094))))|/despacho/(?|([0-9]+)(*:7127)|([0-9]+)/guardar(*:7152)|([0-9]+)/print(*:7175)|([0-9]+)/obtener\\-despachos(*:7211)|([0-9]+)/factura\\-datos(*:7243))|/gestion\\-bodega/pedidos/([^/]++)/entregar(*:7295))/?$}sDu',
    ),
    3 => 
    array (
      50 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.v1.ordenes.show',
          ),
          1 => 
          array (
            0 => 'numero',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      73 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.v1.ordenes.por-cliente',
          ),
          1 => 
          array (
            0 => 'cliente',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      95 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.v1.ordenes.por-estado',
          ),
          1 => 
          array (
            0 => 'estado',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      124 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.v1.ordenes.aprobar',
          ),
          1 => 
          array (
            0 => 'numero',
          ),
          2 => 
          array (
            'PATCH' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      151 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.v1.ordenes.iniciar-produccion',
          ),
          1 => 
          array (
            0 => 'numero',
          ),
          2 => 
          array (
            'PATCH' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      168 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.v1.ordenes.completar',
          ),
          1 => 
          array (
            0 => 'numero',
          ),
          2 => 
          array (
            'PATCH' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      177 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.v1.ordenes.destroy',
          ),
          1 => 
          array (
            0 => 'numero',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      220 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'procesos.activar-recibo',
          ),
          1 => 
          array (
            0 => 'procesoId',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      246 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::BBi6I7UDS0dZu17z',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      286 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'prendas.show',
          ),
          1 => 
          array (
            0 => 'prenda',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      300 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'prendas.search',
          ),
          1 => 
          array (
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      319 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'prendas.update',
          ),
          1 => 
          array (
            0 => 'prenda',
          ),
          2 => 
          array (
            'PUT' => 0,
            'PATCH' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'prendas.destroy',
          ),
          1 => 
          array (
            0 => 'prenda',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      362 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.editar',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'PATCH' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      380 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.editar-campos',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'PATCH' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      394 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.editar-tallas',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'PATCH' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      409 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.estado',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      435 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.proceso-actualizar',
          ),
          1 => 
          array (
            0 => 'prendaId',
            1 => 'procesoId',
          ),
          2 => 
          array (
            'PATCH' => 0,
            'POST' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      477 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.variante-editar',
          ),
          1 => 
          array (
            0 => 'prendaId',
            1 => 'varianteId',
          ),
          2 => 
          array (
            'PATCH' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      492 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.variante-editar-campos',
          ),
          1 => 
          array (
            0 => 'prendaId',
            1 => 'varianteId',
          ),
          2 => 
          array (
            'PATCH' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      506 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.variante-estado',
          ),
          1 => 
          array (
            0 => 'prendaId',
            1 => 'varianteId',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      522 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.variante-colores',
          ),
          1 => 
          array (
            0 => 'prendaId',
            1 => 'varianteId',
          ),
          2 => 
          array (
            'PATCH' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      535 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.variante-telas',
          ),
          1 => 
          array (
            0 => 'prendaId',
            1 => 'varianteId',
          ),
          2 => 
          array (
            'PATCH' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      573 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.prenda.tallas',
          ),
          1 => 
          array (
            0 => 'prendaId',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      590 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.prenda.variantes',
          ),
          1 => 
          array (
            0 => 'prendaId',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      612 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.prenda.colores-telas',
          ),
          1 => 
          array (
            0 => 'prendaId',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      651 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'procesos.prenda.listar',
          ),
          1 => 
          array (
            0 => 'prendaId',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'procesos.prenda.crear',
          ),
          1 => 
          array (
            0 => 'prendaId',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      671 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'procesos.proceso.actualizar',
          ),
          1 => 
          array (
            0 => 'procesoId',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'procesos.proceso.eliminar',
          ),
          1 => 
          array (
            0 => 'procesoId',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      693 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'procesos.proceso.aprobar',
          ),
          1 => 
          array (
            0 => 'procesoId',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      715 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'procesos.proceso.activar-recibo',
          ),
          1 => 
          array (
            0 => 'procesoId',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      732 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'procesos.proceso.rechazar',
          ),
          1 => 
          array (
            0 => 'procesoId',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      751 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'procesos.proceso.imagenes.listar',
          ),
          1 => 
          array (
            0 => 'procesoId',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'procesos.proceso.imagenes.subir',
          ),
          1 => 
          array (
            0 => 'procesoId',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      781 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'procesos.proceso.imagenes.principal',
          ),
          1 => 
          array (
            0 => 'procesoId',
            1 => 'imagenId',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      789 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'procesos.proceso.imagenes.eliminar',
          ),
          1 => 
          array (
            0 => 'procesoId',
            1 => 'imagenId',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      808 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.procesos.editar',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      823 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.procesos.eliminar',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      857 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'pedidos.mostrar',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      881 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'pedidos.listar-por-cliente',
          ),
          1 => 
          array (
            0 => 'clienteId',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      913 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'pedidos.confirmar',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'PATCH' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      928 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'pedidos.cancelar',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      960 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'pedidos.actualizar-descripcion',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'PATCH' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      978 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'pedidos.epps.index',
          ),
          1 => 
          array (
            0 => 'pedido',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'pedidos.epps.store',
          ),
          1 => 
          array (
            0 => 'pedido',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1001 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'pedidos.epps.update',
          ),
          1 => 
          array (
            0 => 'pedido',
            1 => 'pedidoEpp',
          ),
          2 => 
          array (
            'PATCH' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'pedidos.epps.destroy',
          ),
          1 => 
          array (
            0 => 'pedido',
            1 => 'pedidoEpp',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1024 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'pedidos.epps.exportar-json',
          ),
          1 => 
          array (
            0 => 'pedido',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1035 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'pedidos.epp.obtener',
          ),
          1 => 
          array (
            0 => 'pedidoId',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1055 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'pedidos.epp.agregar',
          ),
          1 => 
          array (
            0 => 'pedidoId',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1075 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'pedidos.epp.eliminar',
          ),
          1 => 
          array (
            0 => 'pedidoId',
            1 => 'eppId',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1084 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'pedidos.epp.obtener-por-id',
          ),
          1 => 
          array (
            0 => 'pedidoId',
            1 => 'pedidoEppId',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'pedidos.epp.actualizar',
          ),
          1 => 
          array (
            0 => 'pedidoId',
            1 => 'pedidoEppId',
          ),
          2 => 
          array (
            'PATCH' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1117 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'personal.update-rol',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1155 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'cotizaciones.show',
          ),
          1 => 
          array (
            0 => 'cotizacione',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'cotizaciones.update',
          ),
          1 => 
          array (
            0 => 'cotizacione',
          ),
          2 => 
          array (
            'PUT' => 0,
            'PATCH' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'cotizaciones.destroy',
          ),
          1 => 
          array (
            0 => 'cotizacione',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1199 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'cotizaciones.prendas.telas-cotizacion',
          ),
          1 => 
          array (
            0 => 'cotizacion_id',
            1 => 'prenda_id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1243 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.cartera.aprobar',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1260 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.cartera.rechazar',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1283 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.cartera.factura-datos',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1318 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'epp.imagenes.subir',
          ),
          1 => 
          array (
            0 => 'eppId',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1344 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'epp.imagenes.eliminar',
          ),
          1 => 
          array (
            0 => 'imagenId',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1361 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'epp.show',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1398 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.operario.pedido-data',
          ),
          1 => 
          array (
            0 => 'numeroPedido',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1434 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.ordenes.procesos',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1455 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.ordenes.novedades',
          ),
          1 => 
          array (
            0 => 'numero_pedido',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1468 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.ordenes.novedades.add',
          ),
          1 => 
          array (
            0 => 'numero_pedido',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1497 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'horarios.update',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1535 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.registros.dias',
          ),
          1 => 
          array (
            0 => 'numero_pedido',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1569 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.registros.calcular-fecha-estimada',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1603 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.registros-por-orden',
          ),
          1 => 
          array (
            0 => 'pedido',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1629 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.registros-por-orden-bodega',
          ),
          1 => 
          array (
            0 => 'pedido',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1665 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.logo-pedidos.show',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1717 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.logo-cotizacion-tecnicas.obtener',
          ),
          1 => 
          array (
            0 => 'logoCotizacionId',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1737 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.logo-cotizacion-tecnicas.eliminar',
          ),
          1 => 
          array (
            0 => 'tecnicaId',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1760 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.logo-cotizacion-tecnicas.actualizar-observaciones',
          ),
          1 => 
          array (
            0 => 'tecnicaId',
          ),
          2 => 
          array (
            'PATCH' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1780 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.logo-cotizacion-tecnicas.prendas',
          ),
          1 => 
          array (
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'api.logo-cotizacion-tecnicas.guardar-prenda',
          ),
          1 => 
          array (
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1828 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.tabla-original.procesos',
          ),
          1 => 
          array (
            0 => 'numeroPedido',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1863 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.tabla-original-bodega.procesos',
          ),
          1 => 
          array (
            0 => 'numeroPedido',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1896 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.bodega.dias',
          ),
          1 => 
          array (
            0 => 'numero_pedido',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1917 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.bodega.novedades',
          ),
          1 => 
          array (
            0 => 'pedido',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1930 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.bodega.novedades.add',
          ),
          1 => 
          array (
            0 => 'pedido',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1971 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.valor-hora-extra.obtener',
          ),
          1 => 
          array (
            0 => 'codigoPersona',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1987 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'api.valor-hora-extra.guardar',
          ),
          1 => 
          array (
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2045 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.notifications.mark-read',
          ),
          1 => 
          array (
            0 => 'notificationId',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2082 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.pedidos.show',
          ),
          1 => 
          array (
            0 => 'pedido',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      2096 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.pedidos.edit',
          ),
          1 => 
          array (
            0 => 'pedido',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2105 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.pedidos.update',
          ),
          1 => 
          array (
            0 => 'pedido',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.pedidos.destroy',
          ),
          1 => 
          array (
            0 => 'pedido',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      2138 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.pedidos.factura-datos',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2162 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.pedidos.anular',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2201 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.pedidos.confirmar-correccion',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2237 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.pedidos.actualizar-prenda-completa',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2271 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.pedidos-produccion.show',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      2318 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.pedidos-produccion.obtener-datos-cotizacion',
          ),
          1 => 
          array (
            0 => 'cotizacionId',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      2361 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.pedidos-produccion.obtener-prenda-completa',
          ),
          1 => 
          array (
            0 => 'cotizacionId',
            1 => 'prendaId',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      2401 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.pedidos.prenda-datos',
          ),
          1 => 
          array (
            0 => 'pedidoId',
            1 => 'prendaId',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2441 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.prendas-pedido.fotos',
          ),
          1 => 
          array (
            0 => 'prendaPedidoId',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2481 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.cotizaciones.update',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      2517 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.cotizaciones.imagen.borrar-prenda',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      2539 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.cotizaciones.imagen.borrar-tela',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      2561 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.cotizaciones.imagen.borrar-logo',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      2589 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.cotizaciones.show',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2604 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.cotizaciones.get-for-edit',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2621 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.cotizaciones.subir-imagen',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2631 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.cotizaciones.api',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      2648 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.cotizaciones.guardar',
          ),
          1 => 
          array (
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2688 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.cotizaciones.edit-borrador',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2705 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.cotizaciones.destroy-borrador',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2720 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.cotizaciones.anular',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2730 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.cotizaciones.destroy',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      2749 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.cotizaciones-prenda.store',
          ),
          1 => 
          array (
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2780 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.cotizaciones-prenda.edit',
          ),
          1 => 
          array (
            0 => 'cotizacion',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2794 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.cotizaciones-prenda.enviar',
          ),
          1 => 
          array (
            0 => 'cotizacion',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2804 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.cotizaciones-prenda.update',
          ),
          1 => 
          array (
            0 => 'cotizacion',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.cotizaciones-prenda.destroy',
          ),
          1 => 
          array (
            0 => 'cotizacion',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      2825 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.cotizaciones-bordado.store',
          ),
          1 => 
          array (
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2856 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.cotizaciones-bordado.edit',
          ),
          1 => 
          array (
            0 => 'cotizacion',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2870 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.cotizaciones-bordado.enviar',
          ),
          1 => 
          array (
            0 => 'cotizacion',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2880 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.cotizaciones-bordado.update',
          ),
          1 => 
          array (
            0 => 'cotizacion',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.cotizaciones-bordado.destroy',
          ),
          1 => 
          array (
            0 => 'cotizacion',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      2918 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.cotizacion.pdf.prenda',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2936 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.cotizacion.pdf.combinada',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2949 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.cotizacion.pdf.logo',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2959 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.cotizacion.pdf',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2989 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.clientes.update',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'PATCH' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.clientes.destroy',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      3026 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.api.pedidos.update',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      3045 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.api.pedidos.cambiar-estado',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      3061 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.api.pedidos.agregar-prenda',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      3071 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.api.pedidos.destroy',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      3094 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.api.pedidos.filtrar-estado',
          ),
          1 => 
          array (
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      3118 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.api.pedidos.buscar',
          ),
          1 => 
          array (
            0 => 'numero',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      3143 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.api.pedidos.obtener-prendas',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      3173 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.reportes.update',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'PATCH' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'asesores.reportes.destroy',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      3217 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asistencia-personal.show',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      3233 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asistencia-personal.edit',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      3242 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asistencia-personal.update',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'PATCH' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'asistencia-personal.destroy',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      3265 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asistencia-personal.procesar-pdf',
          ),
          1 => 
          array (
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      3292 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asistencia-personal.validar-registros',
          ),
          1 => 
          array (
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      3322 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asistencia-personal.guardar-registros',
          ),
          1 => 
          array (
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      3352 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asistencia-personal.guardar-asistencia-detallada',
          ),
          1 => 
          array (
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      3382 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asistencia-personal.guardar-hora-extra-agregada',
          ),
          1 => 
          array (
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      3410 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asistencia-personal.guardar-marcas-editadas',
          ),
          1 => 
          array (
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      3428 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asistencia-personal.guardar-marcas-multiples',
          ),
          1 => 
          array (
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      3454 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asistencia-personal.calcular-horas',
          ),
          1 => 
          array (
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      3492 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asistencia-personal.reportes.detalles',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      3510 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asistencia-personal.reportes.ausencias',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      3544 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asistencia-personal.agregar-marca-faltante',
          ),
          1 => 
          array (
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      3591 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asistencia-personal.',
          ),
          1 => 
          array (
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      3634 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asistencia-personal.obtener-horas-extras-agregadas-batch',
          ),
          1 => 
          array (
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      3652 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asistencia-personal.obtener-horas-extras-agregadas',
          ),
          1 => 
          array (
            0 => 'codigo_persona',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      3675 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'asistencia-personal.generated::HQ4BSRixNiYGwgSM',
          ),
          1 => 
          array (
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      3722 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'storage.cotizaciones',
          ),
          1 => 
          array (
            0 => 'path',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      3746 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'storage.prendas',
          ),
          1 => 
          array (
            0 => 'path',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      3766 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'storage.pedidos',
          ),
          1 => 
          array (
            0 => 'path',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      3780 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'storage.local',
          ),
          1 => 
          array (
            0 => 'path',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      3802 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'storage.serve',
          ),
          1 => 
          array (
            0 => 'path',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      3876 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'supervisor-asesores.pedidos.confirmar-correccion',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      3902 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'supervisor-asesores.asesores.show',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      3940 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'supervisor-pedidos.datos',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      3963 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'supervisor-pedidos.factura-datos',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      3980 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'supervisor-pedidos.comparar',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      4027 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'supervisor-pedidos.mark-read',
          ),
          1 => 
          array (
            0 => 'notificationId',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      4061 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'supervisor-pedidos.filtro-opciones',
          ),
          1 => 
          array (
            0 => 'campo',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      4081 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'supervisor-pedidos.show',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      4097 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'supervisor-pedidos.pdf',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      4115 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'supervisor-pedidos.anular',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      4130 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'supervisor-pedidos.aprobar',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      4151 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'supervisor-pedidos.actualizar',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'supervisor-pedidos.actualizar.post',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      4171 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'supervisor-pedidos.cambiar-estado',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'PATCH' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      4185 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'supervisor-pedidos.editar',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      4221 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'supervisor-pedidos.imagen.eliminar',
          ),
          1 => 
          array (
            0 => 'tipo',
            1 => 'id',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      4282 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'pedidos.public.factura-datos',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      4314 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'pedidos.public.recibos-datos',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      4356 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'pedidos.estado.aprobar-supervisor',
          ),
          1 => 
          array (
            0 => 'pedido',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      4374 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'pedidos.estado.historial',
          ),
          1 => 
          array (
            0 => 'pedido',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      4394 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'pedidos.estado.seguimiento',
          ),
          1 => 
          array (
            0 => 'pedido',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      4435 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'prenda-variaciones',
          ),
          1 => 
          array (
            0 => 'tipoPrendaId',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      4475 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'procesos.activar-recibo-simple',
          ),
          1 => 
          array (
            0 => 'procesoId',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      4520 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'notifications.mark-read',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      4547 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'users.update',
          ),
          1 => 
          array (
            0 => 'user',
          ),
          2 => 
          array (
            'PATCH' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      4565 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'users.updatePassword',
          ),
          1 => 
          array (
            0 => 'user',
          ),
          2 => 
          array (
            'PATCH' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      4574 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'users.destroy',
          ),
          1 => 
          array (
            0 => 'user',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      4611 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'entrega.index',
          ),
          1 => 
          array (
            0 => 'tipo',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      4640 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'entrega.costura-data',
          ),
          1 => 
          array (
            0 => 'tipo',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      4658 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'entrega.corte-data',
          ),
          1 => 
          array (
            0 => 'tipo',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      4688 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'entrega.order-data',
          ),
          1 => 
          array (
            0 => 'tipo',
            1 => 'pedido',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      4714 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'entrega.garments',
          ),
          1 => 
          array (
            0 => 'tipo',
            1 => 'pedido',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      4746 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'entrega.sizes',
          ),
          1 => 
          array (
            0 => 'tipo',
            1 => 'pedido',
            2 => 'prenda',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      4782 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'entrega.update',
          ),
          1 => 
          array (
            0 => 'tipo',
            1 => 'subtipo',
            2 => 'id',
          ),
          2 => 
          array (
            'PATCH' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'entrega.destroy',
          ),
          1 => 
          array (
            0 => 'tipo',
            1 => 'subtipo',
            2 => 'id',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      4793 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'entrega.store',
          ),
          1 => 
          array (
            0 => 'tipo',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      4852 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'registros.filter-column-options',
          ),
          1 => 
          array (
            0 => 'column',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      4890 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'registros.recibos-datos',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      4905 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'registros.images',
          ),
          1 => 
          array (
            0 => 'pedido',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      4934 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'registros.descripcion-prendas',
          ),
          1 => 
          array (
            0 => 'pedido',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      4944 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'registros.show',
          ),
          1 => 
          array (
            0 => 'pedido',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      4970 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'registros.validatePedido',
          ),
          1 => 
          array (
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      4996 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'registros.updatePedido',
          ),
          1 => 
          array (
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      5025 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'registros.updateDescripcionPrendas',
          ),
          1 => 
          array (
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      5046 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'registros.update',
          ),
          1 => 
          array (
            0 => 'pedido',
          ),
          2 => 
          array (
            'PATCH' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'registros.destroy',
          ),
          1 => 
          array (
            0 => 'pedido',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      5070 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'registros.updateStatus',
          ),
          1 => 
          array (
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      5099 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'registros.entregas',
          ),
          1 => 
          array (
            0 => 'pedido',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      5117 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'registros.editFull',
          ),
          1 => 
          array (
            0 => 'pedido',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      5150 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'password.reset',
          ),
          1 => 
          array (
            0 => 'token',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      5179 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'orders.show',
          ),
          1 => 
          array (
            0 => 'numero_pedido',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      5214 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'operario.ver-pedido',
          ),
          1 => 
          array (
            0 => 'numeroPedido',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      5245 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'operario.api.pedido',
          ),
          1 => 
          array (
            0 => 'numeroPedido',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      5272 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'operario.api.novedades',
          ),
          1 => 
          array (
            0 => 'numeroPedido',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      5308 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'operario.api.completar-proceso',
          ),
          1 => 
          array (
            0 => 'numeroPedido',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      5341 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'invoices.show',
          ),
          1 => 
          array (
            0 => 'numeroPedido',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      5361 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'invoices.preview',
          ),
          1 => 
          array (
            0 => 'numeroPedido',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      5378 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'invoices.download',
          ),
          1 => 
          array (
            0 => 'numeroPedido',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      5414 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'bodega.show',
          ),
          1 => 
          array (
            0 => 'pedido',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      5434 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'bodega.prendas',
          ),
          1 => 
          array (
            0 => 'pedido',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      5454 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'bodega.entregas',
          ),
          1 => 
          array (
            0 => 'pedido',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      5472 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'bodega.editFull',
          ),
          1 => 
          array (
            0 => 'pedido',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      5500 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'bodega.validatePedido',
          ),
          1 => 
          array (
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      5526 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'bodega.updatePedido',
          ),
          1 => 
          array (
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      5555 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'bodega.updateDescripcionPrendas',
          ),
          1 => 
          array (
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      5573 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'bodega.update',
          ),
          1 => 
          array (
            0 => 'pedido',
          ),
          2 => 
          array (
            'PATCH' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      5620 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'balanceo.prenda.edit',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      5637 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'balanceo.create',
          ),
          1 => 
          array (
            0 => 'prendaId',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      5647 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'balanceo.prenda.update',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'balanceo.prenda.destroy',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'balanceo.show',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      5668 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'balanceo.update',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'PATCH' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'balanceo.destroy',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      5687 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'balanceo.operacion.store',
          ),
          1 => 
          array (
            0 => 'balanceoId',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      5718 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'balanceo.operacion.update',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'PATCH' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'balanceo.operacion.destroy',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      5744 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'balanceo.data',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      5767 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'balanceo.toggle-estado',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      5803 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'tableros.update',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'PATCH' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'tableros.destroy',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      5822 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'tableros.duplicate',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      5855 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'tableros.dashboard-tables-data',
          ),
          1 => 
          array (
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      5886 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'tableros.get-seguimiento-data',
          ),
          1 => 
          array (
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      5909 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'tableros.unique-values',
          ),
          1 => 
          array (
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      5974 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'cotizaciones-prenda.edit',
          ),
          1 => 
          array (
            0 => 'cotizacion',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      5988 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'cotizaciones-prenda.enviar',
          ),
          1 => 
          array (
            0 => 'cotizacion',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      5998 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'cotizaciones-prenda.update',
          ),
          1 => 
          array (
            0 => 'cotizacion',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'cotizaciones-prenda.destroy',
          ),
          1 => 
          array (
            0 => 'cotizacion',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      6042 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'cotizaciones-bordado.update-borrador',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      6060 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'cotizaciones-bordado.borrar-imagen',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      6079 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'cotizaciones-bordado.edit',
          ),
          1 => 
          array (
            0 => 'cotizacion',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      6093 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'cotizaciones-bordado.enviar',
          ),
          1 => 
          array (
            0 => 'cotizacion',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      6104 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'cotizaciones-bordado.update',
          ),
          1 => 
          array (
            0 => 'cotizacion',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'cotizaciones-bordado.destroy',
          ),
          1 => 
          array (
            0 => 'cotizacion',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      6153 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'cotizaciones.borrar-imagen-prenda',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      6166 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'cotizaciones.borrar-imagen-tela',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      6197 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'cotizaciones-bordado.guardar-tela-prenda',
          ),
          1 => 
          array (
            0 => 'cotizacion_id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'cotizaciones-bordado.obtener-telas-prenda',
          ),
          1 => 
          array (
            0 => 'cotizacion_id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      6215 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'cotizaciones-bordado.eliminar-tela-prenda',
          ),
          1 => 
          array (
            0 => 'cotizacion_id',
            1 => 'tela_id',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      6230 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'cotizaciones.obtener-datos',
          ),
          1 => 
          array (
            0 => 'cotizacion',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      6245 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'cotizaciones.obtener-costos',
          ),
          1 => 
          array (
            0 => 'cotizacion',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      6260 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'cotizaciones.estado.enviar',
          ),
          1 => 
          array (
            0 => 'cotizacion',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      6289 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'cotizaciones.estado.aprobar-contador',
          ),
          1 => 
          array (
            0 => 'cotizacion',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      6310 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'cotizaciones.estado.aprobar-para-pedido',
          ),
          1 => 
          array (
            0 => 'cotizacion',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      6328 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'cotizaciones.estado.aprobar-aprobador',
          ),
          1 => 
          array (
            0 => 'cotizacion',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      6346 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'cotizaciones.estado.rechazar',
          ),
          1 => 
          array (
            0 => 'cotizacion',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      6364 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'cotizaciones.estado.historial',
          ),
          1 => 
          array (
            0 => 'cotizacion',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      6384 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'cotizaciones.estado.seguimiento',
          ),
          1 => 
          array (
            0 => 'cotizacion',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      6408 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'cotizacion.pdf',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      6453 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'aprobador.cotizacion.detail',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      6475 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'aprobador.cotizacion.costos',
          ),
          1 => 
          array (
            0 => 'cotizacion',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      6484 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'contador.cotizacion.costos',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      6497 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'contador.cotizacion.pdf',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      6512 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'contador.cotizacion.cambiar-estado',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'PATCH' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      6522 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'contador.cotizacion-delete',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      6553 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'contador.costos.obtener',
          ),
          1 => 
          array (
            0 => 'cotizacion_id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      6595 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'contador.prenda.guardar-notas-tallas',
          ),
          1 => 
          array (
            0 => 'prendaId',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      6632 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'contador.prenda.guardar-texto-personalizado-tallas',
          ),
          1 => 
          array (
            0 => 'prendaId',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      6691 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'visualizador-logo.cotizaciones.ver',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      6710 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'visualizador-logo.cotizaciones.pdf-logo',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      6750 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'verification.verify',
          ),
          1 => 
          array (
            0 => 'id',
            1 => 'hash',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      6793 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'inventario-telas.destroy',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      6811 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'inventario-telas.historial',
          ),
          1 => 
          array (
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      6863 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'insumos.materiales.guardar',
          ),
          1 => 
          array (
            0 => 'pedido',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      6891 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'insumos.materiales.guardar-ancho-metraje',
          ),
          1 => 
          array (
            0 => 'numeroPedido',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      6908 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'insumos.materiales.guardar-ancho-metraje-prenda',
          ),
          1 => 
          array (
            0 => 'numeroPedido',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      6927 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'insumos.materiales.eliminar',
          ),
          1 => 
          array (
            0 => 'pedido',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      6965 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'insumos.materiales.obtener-ancho-metraje',
          ),
          1 => 
          array (
            0 => 'numeroPedido',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      6991 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'insumos.materiales.obtener-ancho-metraje-prenda',
          ),
          1 => 
          array (
            0 => 'numeroPedido',
            1 => 'prendaId',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      7008 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'insumos.materiales.obtener-prendas',
          ),
          1 => 
          array (
            0 => 'numeroPedido',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      7033 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'insumos.materiales.cambiar-estado',
          ),
          1 => 
          array (
            0 => 'numeroPedido',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      7069 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'insumos.api.materiales',
          ),
          1 => 
          array (
            0 => 'pedido',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      7094 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'insumos.api.filtros',
          ),
          1 => 
          array (
            0 => 'column',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      7127 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'despacho.show',
          ),
          1 => 
          array (
            0 => 'pedido',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      7152 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'despacho.guardar',
          ),
          1 => 
          array (
            0 => 'pedido',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      7175 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'despacho.print',
          ),
          1 => 
          array (
            0 => 'pedido',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      7211 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'despacho.obtener',
          ),
          1 => 
          array (
            0 => 'pedido',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      7243 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'despacho.factura-datos',
          ),
          1 => 
          array (
            0 => 'pedido',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      7295 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'gestion-bodega.entregar',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => NULL,
          1 => NULL,
          2 => NULL,
          3 => NULL,
          4 => false,
          5 => false,
          6 => 0,
        ),
      ),
    ),
    4 => NULL,
  ),
  'attributes' => 
  array (
    'generated::ABZYS4cixL6o2jWi' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/asistencia-personal/obtener-todas-las-personas',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\AsistenciaPersonalController@obtenerTodasLasPersonas',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\AsistenciaPersonalController@obtenerTodasLasPersonas',
        'namespace' => NULL,
        'prefix' => 'api/asistencia-personal',
        'where' => 
        array (
        ),
        'as' => 'generated::ABZYS4cixL6o2jWi',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.v1.ordenes.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/api/v1/ordenes',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\V1\\OrdenController@index',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\V1\\OrdenController@index',
        'as' => 'api.v1.ordenes.index',
        'namespace' => NULL,
        'prefix' => 'api/api/v1',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.v1.ordenes.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/api/v1/ordenes/{numero}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\V1\\OrdenController@show',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\V1\\OrdenController@show',
        'as' => 'api.v1.ordenes.show',
        'namespace' => NULL,
        'prefix' => 'api/api/v1',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.v1.ordenes.por-cliente' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/api/v1/ordenes/cliente/{cliente}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\V1\\OrdenController@porCliente',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\V1\\OrdenController@porCliente',
        'as' => 'api.v1.ordenes.por-cliente',
        'namespace' => NULL,
        'prefix' => 'api/api/v1',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.v1.ordenes.por-estado' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/api/v1/ordenes/estado/{estado}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\V1\\OrdenController@porEstado',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\V1\\OrdenController@porEstado',
        'as' => 'api.v1.ordenes.por-estado',
        'namespace' => NULL,
        'prefix' => 'api/api/v1',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.v1.ordenes.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/api/v1/ordenes',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\V1\\OrdenController@store',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\V1\\OrdenController@store',
        'as' => 'api.v1.ordenes.store',
        'namespace' => NULL,
        'prefix' => 'api/api/v1',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.v1.ordenes.aprobar' => 
    array (
      'methods' => 
      array (
        0 => 'PATCH',
      ),
      'uri' => 'api/api/v1/ordenes/{numero}/aprobar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\V1\\OrdenController@aprobar',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\V1\\OrdenController@aprobar',
        'as' => 'api.v1.ordenes.aprobar',
        'namespace' => NULL,
        'prefix' => 'api/api/v1',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.v1.ordenes.iniciar-produccion' => 
    array (
      'methods' => 
      array (
        0 => 'PATCH',
      ),
      'uri' => 'api/api/v1/ordenes/{numero}/iniciar-produccion',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\V1\\OrdenController@iniciarProduccion',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\V1\\OrdenController@iniciarProduccion',
        'as' => 'api.v1.ordenes.iniciar-produccion',
        'namespace' => NULL,
        'prefix' => 'api/api/v1',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.v1.ordenes.completar' => 
    array (
      'methods' => 
      array (
        0 => 'PATCH',
      ),
      'uri' => 'api/api/v1/ordenes/{numero}/completar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\V1\\OrdenController@completar',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\V1\\OrdenController@completar',
        'as' => 'api.v1.ordenes.completar',
        'namespace' => NULL,
        'prefix' => 'api/api/v1',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.v1.ordenes.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/api/v1/ordenes/{numero}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\V1\\OrdenController@destroy',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\V1\\OrdenController@destroy',
        'as' => 'api.v1.ordenes.destroy',
        'namespace' => NULL,
        'prefix' => 'api/api/v1',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'prendas.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/prendas',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'as' => 'prendas.index',
        'uses' => 'App\\Http\\Controllers\\PrendaController@index',
        'controller' => 'App\\Http\\Controllers\\PrendaController@index',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'prendas.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/prendas/{prenda}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'as' => 'prendas.show',
        'uses' => 'App\\Http\\Controllers\\PrendaController@show',
        'controller' => 'App\\Http\\Controllers\\PrendaController@show',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'prendas.search' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/prendas/search',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\PrendaController@search',
        'controller' => 'App\\Http\\Controllers\\PrendaController@search',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'prendas.search',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'pedidos.mostrar' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/pedidos/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\PedidoController@show',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\PedidoController@show',
        'as' => 'pedidos.mostrar',
        'namespace' => NULL,
        'prefix' => 'api/pedidos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'pedidos.listar-por-cliente' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/pedidos/cliente/{clienteId}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\PedidoController@listarPorCliente',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\PedidoController@listarPorCliente',
        'as' => 'pedidos.listar-por-cliente',
        'namespace' => NULL,
        'prefix' => 'api/pedidos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'prendas.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/prendas',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'web',
          2 => 'auth',
        ),
        'excluded_middleware' => 
        array (
          0 => 'api',
        ),
        'as' => 'prendas.store',
        'uses' => 'App\\Http\\Controllers\\PrendaController@store',
        'controller' => 'App\\Http\\Controllers\\PrendaController@store',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'prendas.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
        1 => 'PATCH',
      ),
      'uri' => 'api/prendas/{prenda}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'web',
          2 => 'auth',
        ),
        'excluded_middleware' => 
        array (
          0 => 'api',
        ),
        'as' => 'prendas.update',
        'uses' => 'App\\Http\\Controllers\\PrendaController@update',
        'controller' => 'App\\Http\\Controllers\\PrendaController@update',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'prendas.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/prendas/{prenda}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'web',
          2 => 'auth',
        ),
        'excluded_middleware' => 
        array (
          0 => 'api',
        ),
        'as' => 'prendas.destroy',
        'uses' => 'App\\Http\\Controllers\\PrendaController@destroy',
        'controller' => 'App\\Http\\Controllers\\PrendaController@destroy',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'pedidos.crear' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/pedidos',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'web',
          2 => 'auth',
        ),
        'excluded_middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\PedidoController@store',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\PedidoController@store',
        'as' => 'pedidos.crear',
        'namespace' => NULL,
        'prefix' => 'api/pedidos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'pedidos.confirmar' => 
    array (
      'methods' => 
      array (
        0 => 'PATCH',
      ),
      'uri' => 'api/pedidos/{id}/confirmar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'web',
          2 => 'auth',
        ),
        'excluded_middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\PedidoController@confirmar',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\PedidoController@confirmar',
        'as' => 'pedidos.confirmar',
        'namespace' => NULL,
        'prefix' => 'api/pedidos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'pedidos.actualizar-descripcion' => 
    array (
      'methods' => 
      array (
        0 => 'PATCH',
      ),
      'uri' => 'api/pedidos/{id}/actualizar-descripcion',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'web',
          2 => 'auth',
        ),
        'excluded_middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\PedidoController@actualizarDescripcion',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\PedidoController@actualizarDescripcion',
        'as' => 'pedidos.actualizar-descripcion',
        'namespace' => NULL,
        'prefix' => 'api/pedidos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'pedidos.cancelar' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/pedidos/{id}/cancelar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'web',
          2 => 'auth',
        ),
        'excluded_middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\PedidoController@cancelar',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\PedidoController@cancelar',
        'as' => 'pedidos.cancelar',
        'namespace' => NULL,
        'prefix' => 'api/pedidos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cotizaciones.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/cotizaciones',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'web',
          2 => 'auth',
        ),
        'excluded_middleware' => 
        array (
          0 => 'api',
        ),
        'as' => 'cotizaciones.index',
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionPrendaController@index',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionPrendaController@index',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cotizaciones.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/cotizaciones',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'web',
          2 => 'auth',
        ),
        'excluded_middleware' => 
        array (
          0 => 'api',
        ),
        'as' => 'cotizaciones.store',
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionPrendaController@store',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionPrendaController@store',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cotizaciones.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/cotizaciones/{cotizacione}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'web',
          2 => 'auth',
        ),
        'excluded_middleware' => 
        array (
          0 => 'api',
        ),
        'as' => 'cotizaciones.show',
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionPrendaController@show',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionPrendaController@show',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cotizaciones.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
        1 => 'PATCH',
      ),
      'uri' => 'api/cotizaciones/{cotizacione}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'web',
          2 => 'auth',
        ),
        'excluded_middleware' => 
        array (
          0 => 'api',
        ),
        'as' => 'cotizaciones.update',
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionPrendaController@update',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionPrendaController@update',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cotizaciones.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/cotizaciones/{cotizacione}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'web',
          2 => 'auth',
        ),
        'excluded_middleware' => 
        array (
          0 => 'api',
        ),
        'as' => 'cotizaciones.destroy',
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionPrendaController@destroy',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionPrendaController@destroy',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cotizaciones.prendas.telas-cotizacion' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/cotizaciones/{cotizacion_id}/prendas/{prenda_id}/telas-cotizacion',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'web',
          2 => 'auth',
        ),
        'excluded_middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionPrendaController@obtenerTelasCotizacion',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionPrendaController@obtenerTelasCotizacion',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'cotizaciones.prendas.telas-cotizacion',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'procesos.tipos' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/procesos/tipos',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\ProcesosController@tipos',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\ProcesosController@tipos',
        'as' => 'procesos.tipos',
        'namespace' => NULL,
        'prefix' => 'api/procesos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'procesos.prenda.listar' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/procesos/prendas/{prendaId}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\ProcesosController@obtenerPorPrenda',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\ProcesosController@obtenerPorPrenda',
        'as' => 'procesos.prenda.listar',
        'namespace' => NULL,
        'prefix' => 'api/procesos/prendas/{prendaId}',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'procesos.prenda.crear' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/procesos/prendas/{prendaId}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\ProcesosController@crear',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\ProcesosController@crear',
        'as' => 'procesos.prenda.crear',
        'namespace' => NULL,
        'prefix' => 'api/procesos/prendas/{prendaId}',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'procesos.proceso.actualizar' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'api/procesos/{procesoId}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\ProcesosController@actualizar',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\ProcesosController@actualizar',
        'as' => 'procesos.proceso.actualizar',
        'namespace' => NULL,
        'prefix' => 'api/procesos/{procesoId}',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'procesos.proceso.eliminar' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/procesos/{procesoId}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\ProcesosController@eliminar',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\ProcesosController@eliminar',
        'as' => 'procesos.proceso.eliminar',
        'namespace' => NULL,
        'prefix' => 'api/procesos/{procesoId}',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'procesos.proceso.aprobar' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/procesos/{procesoId}/aprobar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\ProcesosController@aprobar',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\ProcesosController@aprobar',
        'as' => 'procesos.proceso.aprobar',
        'namespace' => NULL,
        'prefix' => 'api/procesos/{procesoId}',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'procesos.proceso.rechazar' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/procesos/{procesoId}/rechazar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\ProcesosController@rechazar',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\ProcesosController@rechazar',
        'as' => 'procesos.proceso.rechazar',
        'namespace' => NULL,
        'prefix' => 'api/procesos/{procesoId}',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'procesos.proceso.activar-recibo' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/procesos/{procesoId}/activar-recibo',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\ProcesosController@activarRecibo',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\ProcesosController@activarRecibo',
        'as' => 'procesos.proceso.activar-recibo',
        'namespace' => NULL,
        'prefix' => 'api/procesos/{procesoId}',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'procesos.proceso.imagenes.listar' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/procesos/{procesoId}/imagenes',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\ProcesosController@obtenerImagenes',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\ProcesosController@obtenerImagenes',
        'as' => 'procesos.proceso.imagenes.listar',
        'namespace' => NULL,
        'prefix' => 'api/procesos/{procesoId}/imagenes',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'procesos.proceso.imagenes.subir' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/procesos/{procesoId}/imagenes',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\ProcesosController@subirImagen',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\ProcesosController@subirImagen',
        'as' => 'procesos.proceso.imagenes.subir',
        'namespace' => NULL,
        'prefix' => 'api/procesos/{procesoId}/imagenes',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'procesos.proceso.imagenes.principal' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/procesos/{procesoId}/imagenes/{imagenId}/principal',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\ProcesosController@marcarComoPrincipal',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\ProcesosController@marcarComoPrincipal',
        'as' => 'procesos.proceso.imagenes.principal',
        'namespace' => NULL,
        'prefix' => 'api/procesos/{procesoId}/imagenes',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'procesos.proceso.imagenes.eliminar' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/procesos/{procesoId}/imagenes/{imagenId}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\ProcesosController@eliminarImagen',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\ProcesosController@eliminarImagen',
        'as' => 'procesos.proceso.imagenes.eliminar',
        'namespace' => NULL,
        'prefix' => 'api/procesos/{procesoId}/imagenes',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'procesos.activar-recibo' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/api/procesos/{procesoId}/activar-recibo',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\ProcesosController@activarRecibo',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\ProcesosController@activarRecibo',
        'namespace' => NULL,
        'prefix' => 'api/api/procesos',
        'where' => 
        array (
        ),
        'as' => 'procesos.activar-recibo',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'epp.imagenes.subir' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/epp/{eppId}/imagenes',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Epp\\EppController@subirImagen',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Epp\\EppController@subirImagen',
        'as' => 'epp.imagenes.subir',
        'namespace' => NULL,
        'prefix' => 'api/epp/{eppId}/imagenes',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'epp.imagenes.upload' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/epp/imagenes/upload',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'uses' => '\\App\\Infrastructure\\Http\\Controllers\\Epp\\EppController@subirImagenEpp',
        'controller' => '\\App\\Infrastructure\\Http\\Controllers\\Epp\\EppController@subirImagenEpp',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'epp.imagenes.upload',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'epp.imagenes.eliminar' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/epp/imagenes/{imagenId}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'uses' => '\\App\\Infrastructure\\Http\\Controllers\\Epp\\EppController@eliminarImagen',
        'controller' => '\\App\\Infrastructure\\Http\\Controllers\\Epp\\EppController@eliminarImagen',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'epp.imagenes.eliminar',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'epp.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/epp',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'uses' => '\\App\\Infrastructure\\Http\\Controllers\\Epp\\EppController@index',
        'controller' => '\\App\\Infrastructure\\Http\\Controllers\\Epp\\EppController@index',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'epp.index',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'epp.buscar' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/epps/buscar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'uses' => '\\App\\Infrastructure\\Http\\Controllers\\Epp\\EppController@buscar',
        'controller' => '\\App\\Infrastructure\\Http\\Controllers\\Epp\\EppController@buscar',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'epp.buscar',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'epp.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/epp',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'uses' => '\\App\\Infrastructure\\Http\\Controllers\\Epp\\EppController@crearEppSimple',
        'controller' => '\\App\\Infrastructure\\Http\\Controllers\\Epp\\EppController@crearEppSimple',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'epp.store',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'epp.debug' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/epp-debug',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:691:"function() {
    try {
        $epps = \\App\\Models\\Epp::where(\'activo\', true)->limit(5)->get();
        return \\response()->json([
            \'success\' => true,
            \'count\' => $epps->count(),
            \'data\' => $epps->map(fn($e) => [
                \'id\' => $e->id,
                \'codigo\' => $e->codigo,
                \'nombre_completo\' => $e->nombre_completo,
                \'activo\' => $e->activo,
            ])->toArray(),
        ]);
    } catch (\\Exception $e) {
        return \\response()->json([
            \'success\' => false,
            \'error\' => $e->getMessage(),
            \'file\' => $e->getFile(),
            \'line\' => $e->getLine(),
        ], 500);
    }
}";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"0000000000000b240000000000000000";}}',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'epp.debug',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'epp.categorias' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/epp/categorias/all',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'uses' => '\\App\\Infrastructure\\Http\\Controllers\\Epp\\EppController@categorias',
        'controller' => '\\App\\Infrastructure\\Http\\Controllers\\Epp\\EppController@categorias',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'epp.categorias',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'epp.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/epp/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'uses' => '\\App\\Infrastructure\\Http\\Controllers\\Epp\\EppController@show',
        'controller' => '\\App\\Infrastructure\\Http\\Controllers\\Epp\\EppController@show',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'epp.show',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'pedidos.epps.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/pedidos/{pedido}/epps',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'uses' => 'App\\Modules\\Pedidos\\Infrastructure\\Http\\Controllers\\PedidoEppController@index',
        'controller' => 'App\\Modules\\Pedidos\\Infrastructure\\Http\\Controllers\\PedidoEppController@index',
        'as' => 'pedidos.epps.index',
        'namespace' => NULL,
        'prefix' => 'api/pedidos/{pedido}/epps',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'pedidos.epps.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/pedidos/{pedido}/epps',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'uses' => 'App\\Modules\\Pedidos\\Infrastructure\\Http\\Controllers\\PedidoEppController@store',
        'controller' => 'App\\Modules\\Pedidos\\Infrastructure\\Http\\Controllers\\PedidoEppController@store',
        'as' => 'pedidos.epps.store',
        'namespace' => NULL,
        'prefix' => 'api/pedidos/{pedido}/epps',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'pedidos.epps.update' => 
    array (
      'methods' => 
      array (
        0 => 'PATCH',
      ),
      'uri' => 'api/pedidos/{pedido}/epps/{pedidoEpp}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'uses' => 'App\\Modules\\Pedidos\\Infrastructure\\Http\\Controllers\\PedidoEppController@update',
        'controller' => 'App\\Modules\\Pedidos\\Infrastructure\\Http\\Controllers\\PedidoEppController@update',
        'as' => 'pedidos.epps.update',
        'namespace' => NULL,
        'prefix' => 'api/pedidos/{pedido}/epps',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'pedidos.epps.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/pedidos/{pedido}/epps/{pedidoEpp}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'uses' => 'App\\Modules\\Pedidos\\Infrastructure\\Http\\Controllers\\PedidoEppController@destroy',
        'controller' => 'App\\Modules\\Pedidos\\Infrastructure\\Http\\Controllers\\PedidoEppController@destroy',
        'as' => 'pedidos.epps.destroy',
        'namespace' => NULL,
        'prefix' => 'api/pedidos/{pedido}/epps',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'pedidos.epps.exportar-json' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/pedidos/{pedido}/epps/exportar/json',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'uses' => 'App\\Modules\\Pedidos\\Infrastructure\\Http\\Controllers\\PedidoEppController@exportarJson',
        'controller' => 'App\\Modules\\Pedidos\\Infrastructure\\Http\\Controllers\\PedidoEppController@exportarJson',
        'as' => 'pedidos.epps.exportar-json',
        'namespace' => NULL,
        'prefix' => 'api/pedidos/{pedido}/epps',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'pedidos.epp.obtener' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/pedidos/{pedidoId}/epp',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'uses' => '\\App\\Infrastructure\\Http\\Controllers\\Epp\\EppController@obtenerDelPedido',
        'controller' => '\\App\\Infrastructure\\Http\\Controllers\\Epp\\EppController@obtenerDelPedido',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'pedidos.epp.obtener',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'pedidos.epp.agregar' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/pedidos/{pedidoId}/epp/agregar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'uses' => '\\App\\Infrastructure\\Http\\Controllers\\Epp\\EppController@agregar',
        'controller' => '\\App\\Infrastructure\\Http\\Controllers\\Epp\\EppController@agregar',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'pedidos.epp.agregar',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'pedidos.epp.eliminar' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/pedidos/{pedidoId}/epp/{eppId}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'uses' => '\\App\\Infrastructure\\Http\\Controllers\\Epp\\EppController@eliminar',
        'controller' => '\\App\\Infrastructure\\Http\\Controllers\\Epp\\EppController@eliminar',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'pedidos.epp.eliminar',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'pedidos.epp.obtener-por-id' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/pedidos/{pedidoId}/epp/{pedidoEppId}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'web',
        ),
        'uses' => '\\App\\Infrastructure\\Http\\Controllers\\Epp\\EppController@obtenerEppDelPedidoPorId',
        'controller' => '\\App\\Infrastructure\\Http\\Controllers\\Epp\\EppController@obtenerEppDelPedidoPorId',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'pedidos.epp.obtener-por-id',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'pedidos.epp.actualizar' => 
    array (
      'methods' => 
      array (
        0 => 'PATCH',
      ),
      'uri' => 'api/pedidos/{pedidoId}/epp/{pedidoEppId}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'web',
        ),
        'uses' => '\\App\\Infrastructure\\Http\\Controllers\\Epp\\EppController@actualizarEppDelPedido',
        'controller' => '\\App\\Infrastructure\\Http\\Controllers\\Epp\\EppController@actualizarEppDelPedido',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'pedidos.epp.actualizar',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'pedidos-editable.agregar-item' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/pedidos-editable/items/agregar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:web',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\CrearPedidoEditableController@agregarItem',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\CrearPedidoEditableController@agregarItem',
        'as' => 'pedidos-editable.agregar-item',
        'namespace' => NULL,
        'prefix' => 'api/pedidos-editable',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'pedidos-editable.eliminar-item' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/pedidos-editable/items/eliminar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:web',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\CrearPedidoEditableController@eliminarItem',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\CrearPedidoEditableController@eliminarItem',
        'as' => 'pedidos-editable.eliminar-item',
        'namespace' => NULL,
        'prefix' => 'api/pedidos-editable',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'pedidos-editable.obtener-items' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/pedidos-editable/items',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:web',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\CrearPedidoEditableController@obtenerItems',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\CrearPedidoEditableController@obtenerItems',
        'as' => 'pedidos-editable.obtener-items',
        'namespace' => NULL,
        'prefix' => 'api/pedidos-editable',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'pedidos-editable.validar' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/pedidos-editable/validar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:web',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\CrearPedidoEditableController@validarPedido',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\CrearPedidoEditableController@validarPedido',
        'as' => 'pedidos-editable.validar',
        'namespace' => NULL,
        'prefix' => 'api/pedidos-editable',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'pedidos-editable.crear' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/pedidos-editable/crear',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:web',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\CrearPedidoEditableController@crearPedido',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\CrearPedidoEditableController@crearPedido',
        'as' => 'pedidos-editable.crear',
        'namespace' => NULL,
        'prefix' => 'api/pedidos-editable',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'pedidos-editable.subir-imagenes' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/pedidos-editable/subir-imagenes',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:web',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\CrearPedidoEditableController@subirImagenesPrenda',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\CrearPedidoEditableController@subirImagenesPrenda',
        'as' => 'pedidos-editable.subir-imagenes',
        'namespace' => NULL,
        'prefix' => 'api/pedidos-editable',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'pedidos-editable.render-item-card' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/pedidos-editable/render-item-card',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:web',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionController@renderItemCard',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionController@renderItemCard',
        'as' => 'pedidos-editable.render-item-card',
        'namespace' => NULL,
        'prefix' => 'api/pedidos-editable',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.operario.pedido-data' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/operario/pedido/{numeroPedido}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Operario\\OperarioController@getPedidoData',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Operario\\OperarioController@getPedidoData',
        'namespace' => NULL,
        'prefix' => '/api',
        'where' => 
        array (
        ),
        'as' => 'api.operario.pedido-data',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'personal.list' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/personal/list',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\PersonalController@list',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\PersonalController@list',
        'as' => 'personal.list',
        'namespace' => NULL,
        'prefix' => 'api/personal',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'personal.update-rol' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'api/personal/{id}/rol',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\PersonalController@updateRol',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\PersonalController@updateRol',
        'as' => 'personal.update-rol',
        'namespace' => NULL,
        'prefix' => 'api/personal',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'horarios.list' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/horarios/list',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\HorarioController@list',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\HorarioController@list',
        'as' => 'horarios.list',
        'namespace' => NULL,
        'prefix' => 'api/horarios',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'horarios.roles-disponibles' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/horarios/roles-disponibles',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\HorarioController@rolesDisponibles',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\HorarioController@rolesDisponibles',
        'as' => 'horarios.roles-disponibles',
        'namespace' => NULL,
        'prefix' => 'api/horarios',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'horarios.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'api/horarios/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\HorarioController@update',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\HorarioController@update',
        'as' => 'horarios.update',
        'namespace' => NULL,
        'prefix' => 'api/horarios',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'horarios.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/horarios',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\HorarioController@store',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\HorarioController@store',
        'as' => 'horarios.store',
        'namespace' => NULL,
        'prefix' => 'api/horarios',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asistencias.obtener' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/asistencias/obtener',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\AsistenciaDetalladaController@obtenerAsistencias',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\AsistenciaDetalladaController@obtenerAsistencias',
        'as' => 'asistencias.obtener',
        'namespace' => NULL,
        'prefix' => 'api/asistencias',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asistencias.dia' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/asistencias/dia',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\AsistenciaDetalladaController@obtenerAsistenciaDelDia',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\AsistenciaDetalladaController@obtenerAsistenciaDelDia',
        'as' => 'asistencias.dia',
        'namespace' => NULL,
        'prefix' => 'api/asistencias',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asistencias.rellenar-inteligente' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/asistencias/rellenar-inteligente',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\AsistenciaDetalladaController@rellenarInteligente',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\AsistenciaDetalladaController@rellenarInteligente',
        'as' => 'asistencias.rellenar-inteligente',
        'namespace' => NULL,
        'prefix' => 'api/asistencias',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asistencias.guardar' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/asistencias/guardar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\AsistenciaDetalladaController@guardarCambios',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\AsistenciaDetalladaController@guardarCambios',
        'as' => 'asistencias.guardar',
        'namespace' => NULL,
        'prefix' => 'api/asistencias',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asistencias.mes' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/asistencias/mes',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\AsistenciaDetalladaController@obtenerMes',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\AsistenciaDetalladaController@obtenerMes',
        'as' => 'asistencias.mes',
        'namespace' => NULL,
        'prefix' => 'api/asistencias',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::QSObNR7of3xTjSNk' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/articulos/guardar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\ArticulosImportController@guardarArticulos',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\ArticulosImportController@guardarArticulos',
        'namespace' => NULL,
        'prefix' => 'api/articulos',
        'where' => 
        array (
        ),
        'as' => 'generated::QSObNR7of3xTjSNk',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::KCwD7aNjCRYkvIqQ' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/articulos',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\ArticulosImportController@listar',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\ArticulosImportController@listar',
        'namespace' => NULL,
        'prefix' => 'api/articulos',
        'where' => 
        array (
        ),
        'as' => 'generated::KCwD7aNjCRYkvIqQ',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::BBi6I7UDS0dZu17z' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/articulos/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\ArticulosImportController@obtener',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\ArticulosImportController@obtener',
        'namespace' => NULL,
        'prefix' => 'api/articulos',
        'where' => 
        array (
        ),
        'as' => 'generated::BBi6I7UDS0dZu17z',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::lOixkeDn6HvwIDEE' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/test-image',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\TestImageController@processImage',
        'controller' => 'App\\Http\\Controllers\\TestImageController@processImage',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::lOixkeDn6HvwIDEE',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.cartera.list' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/cartera/pedidos',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:cartera,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\CarteraPedidosController@obtenerPedidos',
        'controller' => 'App\\Http\\Controllers\\CarteraPedidosController@obtenerPedidos',
        'as' => 'api.cartera.list',
        'namespace' => NULL,
        'prefix' => '/api/cartera',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::gnAFfshis4Up2SoD' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'up',
      'action' => 
      array (
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:847:"function () {
                    $exception = null;

                    try {
                        \\Illuminate\\Support\\Facades\\Event::dispatch(new \\Illuminate\\Foundation\\Events\\DiagnosingHealth);
                    } catch (\\Throwable $e) {
                        if (app()->hasDebugModeEnabled()) {
                            throw $e;
                        }

                        report($e);

                        $exception = $e->getMessage();
                    }

                    return response(\\Illuminate\\Support\\Facades\\View::file(\'C:\\\\Users\\\\Usuario\\\\Documents\\\\mundoindustrial\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Configuration\'.\'/../resources/health-up.blade.php\', [
                        \'exception\' => $exception,
                    ]), status: $exception ? 500 : 200);
                }";s:5:"scope";s:54:"Illuminate\\Foundation\\Configuration\\ApplicationBuilder";s:4:"this";N;s:4:"self";s:32:"0000000000000af40000000000000000";}}',
        'as' => 'generated::gnAFfshis4Up2SoD',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::0J0EUPNDnts0Sa0g' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => '/',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:44:"function () {
    return \\view(\'welcome\');
}";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"0000000000000b4d0000000000000000";}}',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'generated::0J0EUPNDnts0Sa0g',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'test.echo' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'test-echo',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:46:"function () {
    return \\view(\'test-echo\');
}";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"0000000000000b4f0000000000000000";}}',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'test.echo',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'test.pdf-upload' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'test-pdf-upload',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:52:"function () {
    return \\view(\'test-pdf-upload\');
}";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"0000000000000b510000000000000000";}}',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'test.pdf-upload',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'storage.cotizaciones' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'storage/cotizaciones/{path}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:1749:"function ($path) {
    $disk = \\Illuminate\\Support\\Facades\\Storage::disk(\'public\');
    
    // Reconstruir la ruta completa (puede tener múltiples segmentos)
    $fullPath = \'cotizaciones/\' . $path;
    
    // Intentar servir el archivo tal cual
    if ($disk->exists($fullPath)) {
        $contents = $disk->get($fullPath);
        $mimeType = $disk->mimeType($fullPath);
        
        return \\response($contents, 200)
            ->header(\'Content-Type\', $mimeType)
            ->header(\'Cache-Control\', \'public, max-age=31536000\')
            ->header(\'Content-Disposition\', \'inline\');
    }
    
    // Si no existe y termina en .png, intentar .webp
    if (\\str_ends_with($fullPath, \'.png\')) {
        $pathWebp = \\substr($fullPath, 0, -4) . \'.webp\';
        if ($disk->exists($pathWebp)) {
            $contents = $disk->get($pathWebp);
            return \\response($contents, 200)
                ->header(\'Content-Type\', \'image/webp\')
                ->header(\'Cache-Control\', \'public, max-age=31536000\')
                ->header(\'Content-Disposition\', \'inline\');
        }
    }
    
    // Si no existe y termina en .jpg/.jpeg, intentar .webp
    if (\\str_ends_with($fullPath, \'.jpg\') || \\str_ends_with($fullPath, \'.jpeg\')) {
        $pathWebp = \\preg_replace(\'/\\.(jpg|jpeg)$/i\', \'.webp\', $fullPath);
        if ($disk->exists($pathWebp)) {
            $contents = $disk->get($pathWebp);
            return \\response($contents, 200)
                ->header(\'Content-Type\', \'image/webp\')
                ->header(\'Cache-Control\', \'public, max-age=31536000\')
                ->header(\'Content-Disposition\', \'inline\');
        }
    }
    
    // Si no existe en ningún formato, devolver 404
    \\abort(404, \'Imagen no encontrada\');
}";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"0000000000000b530000000000000000";}}',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'storage.cotizaciones',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'path' => '.*',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'storage.prendas' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'storage/prendas/{path}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:1744:"function ($path) {
    $disk = \\Illuminate\\Support\\Facades\\Storage::disk(\'public\');
    
    // Reconstruir la ruta completa (puede tener múltiples segmentos)
    $fullPath = \'prendas/\' . $path;
    
    // Intentar servir el archivo tal cual
    if ($disk->exists($fullPath)) {
        $contents = $disk->get($fullPath);
        $mimeType = $disk->mimeType($fullPath);
        
        return \\response($contents, 200)
            ->header(\'Content-Type\', $mimeType)
            ->header(\'Cache-Control\', \'public, max-age=31536000\')
            ->header(\'Content-Disposition\', \'inline\');
    }
    
    // Si no existe y termina en .png, intentar .webp
    if (\\str_ends_with($fullPath, \'.png\')) {
        $pathWebp = \\substr($fullPath, 0, -4) . \'.webp\';
        if ($disk->exists($pathWebp)) {
            $contents = $disk->get($pathWebp);
            return \\response($contents, 200)
                ->header(\'Content-Type\', \'image/webp\')
                ->header(\'Cache-Control\', \'public, max-age=31536000\')
                ->header(\'Content-Disposition\', \'inline\');
        }
    }
    
    // Si no existe y termina en .jpg/.jpeg, intentar .webp
    if (\\str_ends_with($fullPath, \'.jpg\') || \\str_ends_with($fullPath, \'.jpeg\')) {
        $pathWebp = \\preg_replace(\'/\\.(jpg|jpeg)$/i\', \'.webp\', $fullPath);
        if ($disk->exists($pathWebp)) {
            $contents = $disk->get($pathWebp);
            return \\response($contents, 200)
                ->header(\'Content-Type\', \'image/webp\')
                ->header(\'Cache-Control\', \'public, max-age=31536000\')
                ->header(\'Content-Disposition\', \'inline\');
        }
    }
    
    // Si no existe en ningún formato, devolver 404
    \\abort(404, \'Imagen no encontrada\');
}";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"0000000000000b550000000000000000";}}',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'storage.prendas',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'path' => '.*',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'storage.pedidos' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'storage/pedidos/{path}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:1744:"function ($path) {
    $disk = \\Illuminate\\Support\\Facades\\Storage::disk(\'public\');
    
    // Reconstruir la ruta completa (puede tener múltiples segmentos)
    $fullPath = \'pedidos/\' . $path;
    
    // Intentar servir el archivo tal cual
    if ($disk->exists($fullPath)) {
        $contents = $disk->get($fullPath);
        $mimeType = $disk->mimeType($fullPath);
        
        return \\response($contents, 200)
            ->header(\'Content-Type\', $mimeType)
            ->header(\'Cache-Control\', \'public, max-age=31536000\')
            ->header(\'Content-Disposition\', \'inline\');
    }
    
    // Si no existe y termina en .png, intentar .webp
    if (\\str_ends_with($fullPath, \'.png\')) {
        $pathWebp = \\substr($fullPath, 0, -4) . \'.webp\';
        if ($disk->exists($pathWebp)) {
            $contents = $disk->get($pathWebp);
            return \\response($contents, 200)
                ->header(\'Content-Type\', \'image/webp\')
                ->header(\'Cache-Control\', \'public, max-age=31536000\')
                ->header(\'Content-Disposition\', \'inline\');
        }
    }
    
    // Si no existe y termina en .jpg/.jpeg, intentar .webp
    if (\\str_ends_with($fullPath, \'.jpg\') || \\str_ends_with($fullPath, \'.jpeg\')) {
        $pathWebp = \\preg_replace(\'/\\.(jpg|jpeg)$/i\', \'.webp\', $fullPath);
        if ($disk->exists($pathWebp)) {
            $contents = $disk->get($pathWebp);
            return \\response($contents, 200)
                ->header(\'Content-Type\', \'image/webp\')
                ->header(\'Cache-Control\', \'public, max-age=31536000\')
                ->header(\'Content-Disposition\', \'inline\');
        }
    }
    
    // Si no existe en ningún formato, devolver 404
    \\abort(404, \'Imagen no encontrada\');
}";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"0000000000000b570000000000000000";}}',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'storage.pedidos',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'path' => '.*',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'dashboard' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'dashboard',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
          3 => 'supervisor-access',
        ),
        'uses' => 'App\\Http\\Controllers\\DashboardController@index',
        'controller' => 'App\\Http\\Controllers\\DashboardController@index',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'dashboard',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'profile.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'profile',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\ProfileController@edit',
        'controller' => 'App\\Http\\Controllers\\ProfileController@edit',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'profile.edit',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'profile.update' => 
    array (
      'methods' => 
      array (
        0 => 'PATCH',
      ),
      'uri' => 'profile',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\ProfileController@update',
        'controller' => 'App\\Http\\Controllers\\ProfileController@update',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'profile.update',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'profile.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'profile',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\ProfileController@destroy',
        'controller' => 'App\\Http\\Controllers\\ProfileController@destroy',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'profile.destroy',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'pedidos.public.factura-datos' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'pedidos-public/{id}/factura-datos',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\AsesoresController@obtenerDatosFactura',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\AsesoresController@obtenerDatosFactura',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'pedidos.public.factura-datos',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'id' => '[0-9]+',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'pedidos.public.recibos-datos' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'pedidos-public/{id}/recibos-datos',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\PedidoController@obtenerDetalleCompleto',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\PedidoController@obtenerDetalleCompleto',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'pedidos.public.recibos-datos',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'id' => '[0-9]+',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'refresh.csrf' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'refresh-csrf',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:157:"function () {
        return \\response()->json([
            \'token\' => \\csrf_token(),
            \'timestamp\' => \\now()->toIso8601String()
        ]);
    }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"0000000000000b610000000000000000";}}',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'refresh.csrf',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'fotos.eliminar-inmediatamente' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'asesores/fotos/eliminar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionController@eliminarFotoInmediatamente',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionController@eliminarFotoInmediatamente',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'fotos.eliminar-inmediatamente',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'notifications.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'notifications',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\NotificationController@index',
        'controller' => 'App\\Http\\Controllers\\NotificationController@index',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'notifications.index',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'notifications.unread-count' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'notifications/unread-count',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\NotificationController@getUnreadCount',
        'controller' => 'App\\Http\\Controllers\\NotificationController@getUnreadCount',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'notifications.unread-count',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'notifications.mark-read' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'notifications/{id}/mark-read',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\NotificationController@markAsRead',
        'controller' => 'App\\Http\\Controllers\\NotificationController@markAsRead',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'notifications.mark-read',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'notifications.mark-multiple-read' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'notifications/mark-multiple-read',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\NotificationController@markMultipleAsRead',
        'controller' => 'App\\Http\\Controllers\\NotificationController@markMultipleAsRead',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'notifications.mark-multiple-read',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'notifications.mark-all-read' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'notifications/mark-all-read',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\NotificationController@markAllAsRead',
        'controller' => 'App\\Http\\Controllers\\NotificationController@markAllAsRead',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'notifications.mark-all-read',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'notifications.mark-read-on-open' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'notifications/mark-read-on-open',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\NotificationController@markAsReadOnOpen',
        'controller' => 'App\\Http\\Controllers\\NotificationController@markAsReadOnOpen',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'notifications.mark-read-on-open',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'contador.notifications.mark-all-read' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'contador/notifications/marcar-leidas',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\ContadorController@markAllNotificationsAsRead',
        'controller' => 'App\\Http\\Controllers\\ContadorController@markAllNotificationsAsRead',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'contador.notifications.mark-all-read',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'contador.notifications' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'contador/notifications',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\ContadorController@getNotifications',
        'controller' => 'App\\Http\\Controllers\\ContadorController@getNotifications',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'contador.notifications',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.notifications.mark-all-read' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'asesores/notifications/mark-all-read',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\AsesoresController@markAllAsRead',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\AsesoresController@markAllAsRead',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'asesores.notifications.mark-all-read',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.notifications.mark-read' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'asesores/notifications/{notificationId}/mark-read',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\AsesoresController@markNotificationAsRead',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\AsesoresController@markNotificationAsRead',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'asesores.notifications.mark-read',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.notifications' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/notifications',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\AsesoresController@getNotifications',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\AsesoresController@getNotifications',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'asesores.notifications',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'supervisor-pedidos.notifications.mark-all-read' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'supervisor-pedidos/notifications/mark-all-read',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\SupervisorPedidosController@markAllNotificationsAsRead',
        'controller' => 'App\\Http\\Controllers\\SupervisorPedidosController@markAllNotificationsAsRead',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'supervisor-pedidos.notifications.mark-all-read',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'insumos.notifications.mark-all-read' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'insumos/notifications/marcar-leidas',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Insumos\\InsumosController@markAllNotificationsAsRead',
        'controller' => 'App\\Http\\Controllers\\Insumos\\InsumosController@markAllNotificationsAsRead',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'insumos.notifications.mark-all-read',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'users.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'users',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-access',
        ),
        'uses' => 'App\\Http\\Controllers\\UserController@index',
        'controller' => 'App\\Http\\Controllers\\UserController@index',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'users.index',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'users.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'users',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-access',
        ),
        'uses' => 'App\\Http\\Controllers\\UserController@store',
        'controller' => 'App\\Http\\Controllers\\UserController@store',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'users.store',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'users.update' => 
    array (
      'methods' => 
      array (
        0 => 'PATCH',
      ),
      'uri' => 'users/{user}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-access',
        ),
        'uses' => 'App\\Http\\Controllers\\UserController@update',
        'controller' => 'App\\Http\\Controllers\\UserController@update',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'users.update',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'users.updatePassword' => 
    array (
      'methods' => 
      array (
        0 => 'PATCH',
      ),
      'uri' => 'users/{user}/password',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-access',
        ),
        'uses' => 'App\\Http\\Controllers\\UserController@updatePassword',
        'controller' => 'App\\Http\\Controllers\\UserController@updatePassword',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'users.updatePassword',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'users.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'users/{user}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-access',
        ),
        'uses' => 'App\\Http\\Controllers\\UserController@destroy',
        'controller' => 'App\\Http\\Controllers\\UserController@destroy',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'users.destroy',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'dashboard.entregas-costura-data' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'dashboard/entregas-costura-data',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-access',
        ),
        'uses' => 'App\\Http\\Controllers\\DashboardController@getEntregasCosturaData',
        'controller' => 'App\\Http\\Controllers\\DashboardController@getEntregasCosturaData',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'dashboard.entregas-costura-data',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'dashboard.entregas-corte-data' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'dashboard/entregas-corte-data',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-access',
        ),
        'uses' => 'App\\Http\\Controllers\\DashboardController@getEntregasCorteData',
        'controller' => 'App\\Http\\Controllers\\DashboardController@getEntregasCorteData',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'dashboard.entregas-corte-data',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'dashboard.kpis' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'dashboard/kpis',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-access',
        ),
        'uses' => 'App\\Http\\Controllers\\DashboardController@getKPIs',
        'controller' => 'App\\Http\\Controllers\\DashboardController@getKPIs',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'dashboard.kpis',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'dashboard.recent-orders' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'dashboard/recent-orders',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-access',
        ),
        'uses' => 'App\\Http\\Controllers\\DashboardController@getRecentOrders',
        'controller' => 'App\\Http\\Controllers\\DashboardController@getRecentOrders',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'dashboard.recent-orders',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'dashboard.news' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'dashboard/news',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-access',
        ),
        'uses' => 'App\\Http\\Controllers\\DashboardController@getNews',
        'controller' => 'App\\Http\\Controllers\\DashboardController@getNews',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'dashboard.news',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'dashboard.admin-notifications' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'dashboard/admin-notifications',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-access',
        ),
        'uses' => 'App\\Http\\Controllers\\DashboardController@getAdminNotifications',
        'controller' => 'App\\Http\\Controllers\\DashboardController@getAdminNotifications',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'dashboard.admin-notifications',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'dashboard.news.mark-all-read' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'dashboard/news/mark-all-read',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-access',
        ),
        'uses' => 'App\\Http\\Controllers\\DashboardController@markAllAsRead',
        'controller' => 'App\\Http\\Controllers\\DashboardController@markAllAsRead',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'dashboard.news.mark-all-read',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'dashboard.audit-stats' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'dashboard/audit-stats',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-access',
        ),
        'uses' => 'App\\Http\\Controllers\\DashboardController@getAuditStats',
        'controller' => 'App\\Http\\Controllers\\DashboardController@getAuditStats',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'dashboard.audit-stats',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'entrega.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'entrega/{tipo}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-access',
        ),
        'uses' => 'App\\Http\\Controllers\\EntregaController@index',
        'controller' => 'App\\Http\\Controllers\\EntregaController@index',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'entrega.index',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'tipo' => 'pedido|bodega',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'entrega.costura-data' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'entrega/{tipo}/costura-data',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-access',
        ),
        'uses' => 'App\\Http\\Controllers\\EntregaController@costuraData',
        'controller' => 'App\\Http\\Controllers\\EntregaController@costuraData',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'entrega.costura-data',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'tipo' => 'pedido|bodega',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'entrega.corte-data' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'entrega/{tipo}/corte-data',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-access',
        ),
        'uses' => 'App\\Http\\Controllers\\EntregaController@corteData',
        'controller' => 'App\\Http\\Controllers\\EntregaController@corteData',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'entrega.corte-data',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'tipo' => 'pedido|bodega',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'entrega.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'entrega/{tipo}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-access',
        ),
        'uses' => 'App\\Http\\Controllers\\EntregaController@store',
        'controller' => 'App\\Http\\Controllers\\EntregaController@store',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'entrega.store',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'tipo' => 'pedido|bodega',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'entrega.order-data' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'entrega/{tipo}/order-data/{pedido}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-access',
        ),
        'uses' => 'App\\Http\\Controllers\\EntregaController@orderData',
        'controller' => 'App\\Http\\Controllers\\EntregaController@orderData',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'entrega.order-data',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'tipo' => 'pedido|bodega',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'entrega.garments' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'entrega/{tipo}/garments/{pedido}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-access',
        ),
        'uses' => 'App\\Http\\Controllers\\EntregaController@garments',
        'controller' => 'App\\Http\\Controllers\\EntregaController@garments',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'entrega.garments',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'tipo' => 'pedido|bodega',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'entrega.sizes' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'entrega/{tipo}/sizes/{pedido}/{prenda}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-access',
        ),
        'uses' => 'App\\Http\\Controllers\\EntregaController@sizes',
        'controller' => 'App\\Http\\Controllers\\EntregaController@sizes',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'entrega.sizes',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'tipo' => 'pedido|bodega',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'entrega.update' => 
    array (
      'methods' => 
      array (
        0 => 'PATCH',
      ),
      'uri' => 'entrega/{tipo}/{subtipo}/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-access',
        ),
        'uses' => 'App\\Http\\Controllers\\EntregaController@update',
        'controller' => 'App\\Http\\Controllers\\EntregaController@update',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'entrega.update',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'tipo' => 'pedido|bodega',
        'subtipo' => 'costura|corte',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'entrega.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'entrega/{tipo}/{subtipo}/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-access',
        ),
        'uses' => 'App\\Http\\Controllers\\EntregaController@destroy',
        'controller' => 'App\\Http\\Controllers\\EntregaController@destroy',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'entrega.destroy',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'tipo' => 'pedido|bodega',
        'subtipo' => 'costura|corte',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'registros.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'registros',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroOrdenQueryController@index',
        'controller' => 'App\\Http\\Controllers\\RegistroOrdenQueryController@index',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'registros.index',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'registros.next-pedido' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'registros/next-pedido',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroOrdenController@getNextPedido',
        'controller' => 'App\\Http\\Controllers\\RegistroOrdenController@getNextPedido',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'registros.next-pedido',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'registros.filter-options' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'registros/filter-options',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroOrdenController@getFilterOptions',
        'controller' => 'App\\Http\\Controllers\\RegistroOrdenController@getFilterOptions',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'registros.filter-options',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'registros.filter-column-options' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'registros/filter-column-options/{column}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroOrdenController@getColumnFilterOptions',
        'controller' => 'App\\Http\\Controllers\\RegistroOrdenController@getColumnFilterOptions',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'registros.filter-column-options',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'registros.filter-orders' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'registros/filter-orders',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroOrdenController@filterOrders',
        'controller' => 'App\\Http\\Controllers\\RegistroOrdenController@filterOrders',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'registros.filter-orders',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'registros.search' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'registros/search',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroOrdenController@searchOrders',
        'controller' => 'App\\Http\\Controllers\\RegistroOrdenController@searchOrders',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'registros.search',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'registros.recibos-datos' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'registros/{id}/recibos-datos',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\PedidoController@obtenerDetalleCompleto',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\PedidoController@obtenerDetalleCompleto',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'registros.recibos-datos',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'registros.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'registros/{pedido}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroOrdenQueryController@show',
        'controller' => 'App\\Http\\Controllers\\RegistroOrdenQueryController@show',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'registros.show',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'registros.images' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'registros/{pedido}/images',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroOrdenQueryController@getOrderImages',
        'controller' => 'App\\Http\\Controllers\\RegistroOrdenQueryController@getOrderImages',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'registros.images',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'registros.descripcion-prendas' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'registros/{pedido}/descripcion-prendas',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroOrdenQueryController@getDescripcionPrendas',
        'controller' => 'App\\Http\\Controllers\\RegistroOrdenQueryController@getDescripcionPrendas',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'registros.descripcion-prendas',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.registros.dias' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/registros/{numero_pedido}/dias',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroOrdenQueryController@calcularDiasAPI',
        'controller' => 'App\\Http\\Controllers\\RegistroOrdenQueryController@calcularDiasAPI',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'api.registros.dias',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.registros.dias-batch' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/registros/dias-batch',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroOrdenQueryController@calcularDiasBatchAPI',
        'controller' => 'App\\Http\\Controllers\\RegistroOrdenQueryController@calcularDiasBatchAPI',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'api.registros.dias-batch',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.registros.calcular-fecha-estimada' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/registros/{id}/calcular-fecha-estimada',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroOrdenQueryController@calcularFechaEstimada',
        'controller' => 'App\\Http\\Controllers\\RegistroOrdenQueryController@calcularFechaEstimada',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'api.registros.calcular-fecha-estimada',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.logo-pedidos.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/logo-pedidos/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroOrdenQueryController@showLogoPedidoById',
        'controller' => 'App\\Http\\Controllers\\RegistroOrdenQueryController@showLogoPedidoById',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'api.logo-pedidos.show',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'registros.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'registros',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroOrdenController@store',
        'controller' => 'App\\Http\\Controllers\\RegistroOrdenController@store',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'registros.store',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'registros.validatePedido' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'registros/validate-pedido',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroOrdenController@validatePedido',
        'controller' => 'App\\Http\\Controllers\\RegistroOrdenController@validatePedido',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'registros.validatePedido',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'registros.updatePedido' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'registros/update-pedido',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroOrdenController@updatePedido',
        'controller' => 'App\\Http\\Controllers\\RegistroOrdenController@updatePedido',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'registros.updatePedido',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'registros.updateDescripcionPrendas' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'registros/update-descripcion-prendas',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroOrdenController@updateDescripcionPrendas',
        'controller' => 'App\\Http\\Controllers\\RegistroOrdenController@updateDescripcionPrendas',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'registros.updateDescripcionPrendas',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'registros.update' => 
    array (
      'methods' => 
      array (
        0 => 'PATCH',
      ),
      'uri' => 'registros/{pedido}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroOrdenController@update',
        'controller' => 'App\\Http\\Controllers\\RegistroOrdenController@update',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'registros.update',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'registros.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'registros/{pedido}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroOrdenController@destroy',
        'controller' => 'App\\Http\\Controllers\\RegistroOrdenController@destroy',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'registros.destroy',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'registros.updateStatus' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'registros/update-status',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroOrdenController@updateStatus',
        'controller' => 'App\\Http\\Controllers\\RegistroOrdenController@updateStatus',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'registros.updateStatus',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'registros.entregas' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'registros/{pedido}/entregas',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroOrdenController@getEntregas',
        'controller' => 'App\\Http\\Controllers\\RegistroOrdenController@getEntregas',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'registros.entregas',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.registros-por-orden' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/registros-por-orden/{pedido}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroOrdenController@getRegistrosPorOrden',
        'controller' => 'App\\Http\\Controllers\\RegistroOrdenController@getRegistrosPorOrden',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'api.registros-por-orden',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.tabla-original.procesos' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/tabla-original/{numeroPedido}/procesos',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroOrdenController@getProcesosTablaOriginal',
        'controller' => 'App\\Http\\Controllers\\RegistroOrdenController@getProcesosTablaOriginal',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'api.tabla-original.procesos',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'registros.editFull' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'registros/{pedido}/edit-full',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroOrdenController@editFullOrder',
        'controller' => 'App\\Http\\Controllers\\RegistroOrdenController@editFullOrder',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'registros.editFull',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'orders.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'orders/{numero_pedido}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroOrdenController@show',
        'controller' => 'App\\Http\\Controllers\\RegistroOrdenController@show',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'orders.show',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'invoices.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'facturas/{numeroPedido}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\InvoiceController@show',
        'controller' => 'App\\Http\\Controllers\\InvoiceController@show',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'invoices.show',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'invoices.preview' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'facturas/{numeroPedido}/preview',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\InvoiceController@preview',
        'controller' => 'App\\Http\\Controllers\\InvoiceController@preview',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'invoices.preview',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'invoices.download' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'facturas/{numeroPedido}/download',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\InvoiceController@download',
        'controller' => 'App\\Http\\Controllers\\InvoiceController@download',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'invoices.download',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.bodega.dias' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/bodega/{numero_pedido}/dias',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroBodegaController@calcularDiasAPI',
        'controller' => 'App\\Http\\Controllers\\RegistroBodegaController@calcularDiasAPI',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'api.bodega.dias',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.ordenes.procesos' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/ordenes/{id}/procesos',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionController@getProcesos',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionController@getProcesos',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'api.ordenes.procesos',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.ordenes.novedades' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/ordenes/{numero_pedido}/novedades',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroOrdenController@updateNovedades',
        'controller' => 'App\\Http\\Controllers\\RegistroOrdenController@updateNovedades',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'api.ordenes.novedades',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.ordenes.novedades.add' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/ordenes/{numero_pedido}/novedades/add',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroOrdenController@addNovedad',
        'controller' => 'App\\Http\\Controllers\\RegistroOrdenController@addNovedad',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'api.ordenes.novedades.add',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.bodega.novedades' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/bodega/{pedido}/novedades',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroBodegaController@updateNovedadesBodega',
        'controller' => 'App\\Http\\Controllers\\RegistroBodegaController@updateNovedadesBodega',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'api.bodega.novedades',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.bodega.novedades.add' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/bodega/{pedido}/novedades/add',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroBodegaController@addNovedadBodega',
        'controller' => 'App\\Http\\Controllers\\RegistroBodegaController@addNovedadBodega',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'api.bodega.novedades.add',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.procesos.editar' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'api/procesos/{id}/editar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionController@editarProceso',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionController@editarProceso',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'api.procesos.editar',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.procesos.eliminar' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/procesos/{id}/eliminar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionController@eliminarProceso',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionController@eliminarProceso',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'api.procesos.eliminar',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.tabla-original-bodega.procesos' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/tabla-original-bodega/{numeroPedido}/procesos',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroBodegaController@getProcesosTablaOriginal',
        'controller' => 'App\\Http\\Controllers\\RegistroBodegaController@getProcesosTablaOriginal',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'api.tabla-original-bodega.procesos',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'bodega.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'bodega',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroBodegaController@index',
        'controller' => 'App\\Http\\Controllers\\RegistroBodegaController@index',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'bodega.index',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'bodega.search' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'bodega/search',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroBodegaController@searchOrders',
        'controller' => 'App\\Http\\Controllers\\RegistroBodegaController@searchOrders',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'bodega.search',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'bodega.next-pedido' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'bodega/next-pedido',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroBodegaController@getNextPedido',
        'controller' => 'App\\Http\\Controllers\\RegistroBodegaController@getNextPedido',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'bodega.next-pedido',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'bodega.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'bodega/{pedido}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroBodegaController@show',
        'controller' => 'App\\Http\\Controllers\\RegistroBodegaController@show',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'bodega.show',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'bodega.prendas' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'bodega/{pedido}/prendas',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroBodegaController@getPrendas',
        'controller' => 'App\\Http\\Controllers\\RegistroBodegaController@getPrendas',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'bodega.prendas',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'bodega.entregas' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'bodega/{pedido}/entregas',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroBodegaController@getEntregas',
        'controller' => 'App\\Http\\Controllers\\RegistroBodegaController@getEntregas',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'bodega.entregas',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.registros-por-orden-bodega' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/registros-por-orden-bodega/{pedido}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroBodegaController@getRegistrosPorOrden',
        'controller' => 'App\\Http\\Controllers\\RegistroBodegaController@getRegistrosPorOrden',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'api.registros-por-orden-bodega',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'bodega.editFull' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'bodega/{pedido}/edit-full',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroBodegaController@editFullOrder',
        'controller' => 'App\\Http\\Controllers\\RegistroBodegaController@editFullOrder',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'bodega.editFull',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'bodega.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'bodega',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroBodegaController@store',
        'controller' => 'App\\Http\\Controllers\\RegistroBodegaController@store',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'bodega.store',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'bodega.validatePedido' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'bodega/validate-pedido',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroBodegaController@validatePedido',
        'controller' => 'App\\Http\\Controllers\\RegistroBodegaController@validatePedido',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'bodega.validatePedido',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'bodega.updatePedido' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'bodega/update-pedido',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroBodegaController@updatePedido',
        'controller' => 'App\\Http\\Controllers\\RegistroBodegaController@updatePedido',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'bodega.updatePedido',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'bodega.updateDescripcionPrendas' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'bodega/update-descripcion-prendas',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroBodegaController@updateDescripcionPrendas',
        'controller' => 'App\\Http\\Controllers\\RegistroBodegaController@updateDescripcionPrendas',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'bodega.updateDescripcionPrendas',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'bodega.update' => 
    array (
      'methods' => 
      array (
        0 => 'PATCH',
      ),
      'uri' => 'bodega/{pedido}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\RegistroBodegaController@update',
        'controller' => 'App\\Http\\Controllers\\RegistroBodegaController@update',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'bodega.update',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'configuracion.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'configuracion',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\ConfiguracionController@index',
        'controller' => 'App\\Http\\Controllers\\ConfiguracionController@index',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'configuracion.index',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'configuracion.createDatabase' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'configuracion/create-database',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\ConfiguracionController@createDatabase',
        'controller' => 'App\\Http\\Controllers\\ConfiguracionController@createDatabase',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'configuracion.createDatabase',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'configuracion.selectDatabase' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'configuracion/select-database',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\ConfiguracionController@selectDatabase',
        'controller' => 'App\\Http\\Controllers\\ConfiguracionController@selectDatabase',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'configuracion.selectDatabase',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'configuracion.migrateUsers' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'configuracion/migrate-users',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\ConfiguracionController@migrateUsers',
        'controller' => 'App\\Http\\Controllers\\ConfiguracionController@migrateUsers',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'configuracion.migrateUsers',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'configuracion.backupDatabase' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'configuracion/backup-database',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\ConfiguracionController@backupDatabase',
        'controller' => 'App\\Http\\Controllers\\ConfiguracionController@backupDatabase',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'configuracion.backupDatabase',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'configuracion.downloadBackup' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'configuracion/download-backup',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\ConfiguracionController@downloadBackup',
        'controller' => 'App\\Http\\Controllers\\ConfiguracionController@downloadBackup',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'configuracion.downloadBackup',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'configuracion.uploadGoogleDrive' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'configuracion/upload-google-drive',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\ConfiguracionController@uploadToGoogleDrive',
        'controller' => 'App\\Http\\Controllers\\ConfiguracionController@uploadToGoogleDrive',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'configuracion.uploadGoogleDrive',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'tableros.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'tableros',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\TablerosController@index',
        'controller' => 'App\\Http\\Controllers\\TablerosController@index',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'tableros.index',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'tableros.fullscreen' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'tableros/fullscreen',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\TablerosController@fullscreen',
        'controller' => 'App\\Http\\Controllers\\TablerosController@fullscreen',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'tableros.fullscreen',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'tableros.corte-fullscreen' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'tableros/corte-fullscreen',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\TablerosController@corteFullscreen',
        'controller' => 'App\\Http\\Controllers\\TablerosController@corteFullscreen',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'tableros.corte-fullscreen',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'tableros.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'tableros',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\TablerosController@store',
        'controller' => 'App\\Http\\Controllers\\TablerosController@store',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'tableros.store',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'tableros.update' => 
    array (
      'methods' => 
      array (
        0 => 'PATCH',
      ),
      'uri' => 'tableros/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\TablerosController@update',
        'controller' => 'App\\Http\\Controllers\\TablerosController@update',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'tableros.update',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'tableros.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'tableros/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\TablerosController@destroy',
        'controller' => 'App\\Http\\Controllers\\TablerosController@destroy',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'tableros.destroy',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'tableros.duplicate' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'tableros/{id}/duplicate',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\TablerosController@duplicate',
        'controller' => 'App\\Http\\Controllers\\TablerosController@duplicate',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'tableros.duplicate',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'piso-corte.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'piso-corte',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\TablerosController@storeCorte',
        'controller' => 'App\\Http\\Controllers\\TablerosController@storeCorte',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'piso-corte.store',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'get-tiempo-ciclo' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'get-tiempo-ciclo',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\TablerosController@getTiempoCiclo',
        'controller' => 'App\\Http\\Controllers\\TablerosController@getTiempoCiclo',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'get-tiempo-ciclo',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'store-tela' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'store-tela',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\TablerosController@storeTela',
        'controller' => 'App\\Http\\Controllers\\TablerosController@storeTela',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'store-tela',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'search-telas' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'search-telas',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\TablerosController@searchTelas',
        'controller' => 'App\\Http\\Controllers\\TablerosController@searchTelas',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'search-telas',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'store-maquina' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'store-maquina',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\TablerosController@storeMaquina',
        'controller' => 'App\\Http\\Controllers\\TablerosController@storeMaquina',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'store-maquina',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'search-maquinas' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'search-maquinas',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\TablerosController@searchMaquinas',
        'controller' => 'App\\Http\\Controllers\\TablerosController@searchMaquinas',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'search-maquinas',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'search-operarios' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'search-operarios',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\TablerosController@searchOperarios',
        'controller' => 'App\\Http\\Controllers\\TablerosController@searchOperarios',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'search-operarios',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'store-operario' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'store-operario',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\TablerosController@storeOperario',
        'controller' => 'App\\Http\\Controllers\\TablerosController@storeOperario',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'store-operario',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'find-or-create-operario' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'find-or-create-operario',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\TablerosController@findOrCreateOperario',
        'controller' => 'App\\Http\\Controllers\\TablerosController@findOrCreateOperario',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'find-or-create-operario',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'find-or-create-maquina' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'find-or-create-maquina',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\TablerosController@findOrCreateMaquina',
        'controller' => 'App\\Http\\Controllers\\TablerosController@findOrCreateMaquina',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'find-or-create-maquina',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'find-or-create-tela' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'find-or-create-tela',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\TablerosController@findOrCreateTela',
        'controller' => 'App\\Http\\Controllers\\TablerosController@findOrCreateTela',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'find-or-create-tela',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'find-hora-id' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'find-hora-id',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\TablerosController@findHoraId',
        'controller' => 'App\\Http\\Controllers\\TablerosController@findHoraId',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'find-hora-id',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'tableros.dashboard-tables-data' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'tableros/dashboard-tables-data',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\TablerosController@getDashboardTablesData',
        'controller' => 'App\\Http\\Controllers\\TablerosController@getDashboardTablesData',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'tableros.dashboard-tables-data',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'tableros.get-seguimiento-data' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'tableros/get-seguimiento-data',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\TablerosController@getSeguimientoData',
        'controller' => 'App\\Http\\Controllers\\TablerosController@getSeguimientoData',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'tableros.get-seguimiento-data',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'tableros.corte.dashboard' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'tableros/corte/dashboard',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\TablerosController@getDashboardCorteData',
        'controller' => 'App\\Http\\Controllers\\TablerosController@getDashboardCorteData',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'tableros.corte.dashboard',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'tableros.unique-values' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'tableros/unique-values',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\TablerosController@getUniqueValues',
        'controller' => 'App\\Http\\Controllers\\TablerosController@getUniqueValues',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'tableros.unique-values',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'vistas.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'vistas',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\VistasController@index',
        'controller' => 'App\\Http\\Controllers\\VistasController@index',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'vistas.index',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.vistas.search' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/vistas/search',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\VistasController@search',
        'controller' => 'App\\Http\\Controllers\\VistasController@search',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'api.vistas.search',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.vistas.update-cell' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/vistas/update-cell',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\VistasController@updateCell',
        'controller' => 'App\\Http\\Controllers\\VistasController@updateCell',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'api.vistas.update-cell',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'vistas.control-calidad' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'vistas/control-calidad',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\VistasController@controlCalidad',
        'controller' => 'App\\Http\\Controllers\\VistasController@controlCalidad',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'vistas.control-calidad',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'vistas.control-calidad-fullscreen' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'vistas/control-calidad-fullscreen',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\VistasController@controlCalidadFullscreen',
        'controller' => 'App\\Http\\Controllers\\VistasController@controlCalidadFullscreen',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'vistas.control-calidad-fullscreen',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'balanceo.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'balanceo',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\BalanceoController@index',
        'controller' => 'App\\Http\\Controllers\\BalanceoController@index',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'balanceo.index',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'balanceo.prenda.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'balanceo/prenda/create',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\BalanceoController@createPrenda',
        'controller' => 'App\\Http\\Controllers\\BalanceoController@createPrenda',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'balanceo.prenda.create',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'balanceo.prenda.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'balanceo/prenda',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\BalanceoController@storePrenda',
        'controller' => 'App\\Http\\Controllers\\BalanceoController@storePrenda',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'balanceo.prenda.store',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'balanceo.prenda.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'balanceo/prenda/{id}/edit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\BalanceoController@editPrenda',
        'controller' => 'App\\Http\\Controllers\\BalanceoController@editPrenda',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'balanceo.prenda.edit',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'balanceo.prenda.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'balanceo/prenda/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\BalanceoController@updatePrenda',
        'controller' => 'App\\Http\\Controllers\\BalanceoController@updatePrenda',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'balanceo.prenda.update',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'balanceo.prenda.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'balanceo/prenda/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\BalanceoController@destroyPrenda',
        'controller' => 'App\\Http\\Controllers\\BalanceoController@destroyPrenda',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'balanceo.prenda.destroy',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'balanceo.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'balanceo/prenda/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\BalanceoController@show',
        'controller' => 'App\\Http\\Controllers\\BalanceoController@show',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'balanceo.show',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'balanceo.create' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'balanceo/prenda/{prendaId}/balanceo',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\BalanceoController@createBalanceo',
        'controller' => 'App\\Http\\Controllers\\BalanceoController@createBalanceo',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'balanceo.create',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'balanceo.update' => 
    array (
      'methods' => 
      array (
        0 => 'PATCH',
      ),
      'uri' => 'balanceo/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\BalanceoController@updateBalanceo',
        'controller' => 'App\\Http\\Controllers\\BalanceoController@updateBalanceo',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'balanceo.update',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'balanceo.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'balanceo/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\BalanceoController@destroyBalanceo',
        'controller' => 'App\\Http\\Controllers\\BalanceoController@destroyBalanceo',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'balanceo.destroy',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'balanceo.operacion.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'balanceo/{balanceoId}/operacion',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\BalanceoController@storeOperacion',
        'controller' => 'App\\Http\\Controllers\\BalanceoController@storeOperacion',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'balanceo.operacion.store',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'balanceo.operacion.update' => 
    array (
      'methods' => 
      array (
        0 => 'PATCH',
      ),
      'uri' => 'balanceo/operacion/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\BalanceoController@updateOperacion',
        'controller' => 'App\\Http\\Controllers\\BalanceoController@updateOperacion',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'balanceo.operacion.update',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'balanceo.operacion.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'balanceo/operacion/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\BalanceoController@destroyOperacion',
        'controller' => 'App\\Http\\Controllers\\BalanceoController@destroyOperacion',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'balanceo.operacion.destroy',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'balanceo.data' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'balanceo/{id}/data',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\BalanceoController@getBalanceoData',
        'controller' => 'App\\Http\\Controllers\\BalanceoController@getBalanceoData',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'balanceo.data',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'balanceo.toggle-estado' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'balanceo/{id}/toggle-estado',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'supervisor-readonly',
        ),
        'uses' => 'App\\Http\\Controllers\\BalanceoController@toggleEstadoCompleto',
        'controller' => 'App\\Http\\Controllers\\BalanceoController@toggleEstadoCompleto',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'balanceo.toggle-estado',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cotizaciones-prenda.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'cotizaciones-prenda/crear',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionPrendaController@create',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionPrendaController@create',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'cotizaciones-prenda.create',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cotizaciones-prenda.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'cotizaciones-prenda',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionPrendaController@store',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionPrendaController@store',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'cotizaciones-prenda.store',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cotizaciones-prenda.lista' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'cotizaciones-prenda',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionPrendaController@lista',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionPrendaController@lista',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'cotizaciones-prenda.lista',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cotizaciones-prenda.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'cotizaciones-prenda/{cotizacion}/editar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionPrendaController@edit',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionPrendaController@edit',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'cotizaciones-prenda.edit',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cotizaciones-prenda.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'cotizaciones-prenda/{cotizacion}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionPrendaController@update',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionPrendaController@update',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'cotizaciones-prenda.update',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cotizaciones-prenda.enviar' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'cotizaciones-prenda/{cotizacion}/enviar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionPrendaController@enviar',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionPrendaController@enviar',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'cotizaciones-prenda.enviar',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cotizaciones-prenda.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'cotizaciones-prenda/{cotizacion}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionPrendaController@destroy',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionPrendaController@destroy',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'cotizaciones-prenda.destroy',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cotizaciones.borrar-imagen-prenda' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'cotizaciones/{id}/borrar-imagen-prenda',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionController@borrarImagenPrenda',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionController@borrarImagenPrenda',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'cotizaciones.borrar-imagen-prenda',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cotizaciones.borrar-imagen-tela' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'cotizaciones/{id}/borrar-imagen-tela',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionController@borrarImagenTela',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionController@borrarImagenTela',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'cotizaciones.borrar-imagen-tela',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cotizaciones-bordado.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'cotizaciones-bordado/crear',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionBordadoController@create',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionBordadoController@create',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'cotizaciones-bordado.create',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cotizaciones-bordado.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'cotizaciones-bordado',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionBordadoController@store',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionBordadoController@store',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'cotizaciones-bordado.store',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cotizaciones-bordado.update-borrador' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'cotizaciones-bordado/{id}/borrador',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionBordadoController@updateBorrador',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionBordadoController@updateBorrador',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'cotizaciones-bordado.update-borrador',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cotizaciones-bordado.borrar-imagen' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'cotizaciones-bordado/{id}/borrar-imagen',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionBordadoController@borrarImagen',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionBordadoController@borrarImagen',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'cotizaciones-bordado.borrar-imagen',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cotizaciones-bordado.lista' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'cotizaciones-bordado',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionBordadoController@lista',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionBordadoController@lista',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'cotizaciones-bordado.lista',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cotizaciones-bordado.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'cotizaciones-bordado/{cotizacion}/editar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionBordadoController@edit',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionBordadoController@edit',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'cotizaciones-bordado.edit',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cotizaciones-bordado.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'cotizaciones-bordado/{cotizacion}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionBordadoController@update',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionBordadoController@update',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'cotizaciones-bordado.update',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cotizaciones-bordado.enviar' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'cotizaciones-bordado/{cotizacion}/enviar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionBordadoController@enviar',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionBordadoController@enviar',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'cotizaciones-bordado.enviar',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cotizaciones-bordado.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'cotizaciones-bordado/{cotizacion}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionBordadoController@destroy',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionBordadoController@destroy',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'cotizaciones-bordado.destroy',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cotizaciones-bordado.guardar-tela-prenda' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'cotizaciones/{cotizacion_id}/logo/telas-prenda',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionBordadoController@guardarTelaPrenda',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionBordadoController@guardarTelaPrenda',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'cotizaciones-bordado.guardar-tela-prenda',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cotizaciones-bordado.obtener-telas-prenda' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'cotizaciones/{cotizacion_id}/logo/telas-prenda',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionBordadoController@obtenerTelasPrenda',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionBordadoController@obtenerTelasPrenda',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'cotizaciones-bordado.obtener-telas-prenda',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cotizaciones-bordado.eliminar-tela-prenda' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'cotizaciones/{cotizacion_id}/logo/telas-prenda/{tela_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionBordadoController@eliminarTelaPrenda',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionBordadoController@eliminarTelaPrenda',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'cotizaciones-bordado.eliminar-tela-prenda',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'test-telas.crear' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'test-tela-prenda/crear',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\TestTelasPrendaController@crear',
        'controller' => 'App\\Http\\Controllers\\TestTelasPrendaController@crear',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'test-telas.crear',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'test-telas.listar' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'test-tela-prenda/listar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\TestTelasPrendaController@listar',
        'controller' => 'App\\Http\\Controllers\\TestTelasPrendaController@listar',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'test-telas.listar',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'test-telas.limpiar' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'test-tela-prenda/limpiar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\TestTelasPrendaController@limpiar',
        'controller' => 'App\\Http\\Controllers\\TestTelasPrendaController@limpiar',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'test-telas.limpiar',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cotizaciones.pendientes' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'cotizaciones/pendientes',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:929:"function () {
        // Verificar que el usuario tenga el rol aprobador_cotizaciones
        if (!\\auth()->user()->hasRole(\'aprobador_cotizaciones\')) {
            \\abort(403, \'No tienes permiso para acceder a esta sección.\');
        }
        
        // Obtener cotizaciones pendientes de aprobación (estado APROBADA_CONTADOR)
        $cotizaciones = \\App\\Models\\Cotizacion::where(\'estado\', \'APROBADA_CONTADOR\')
            ->with([\'aprobaciones.usuario\'])
            ->orderBy(\'created_at\', \'desc\')
            ->get();
        
        // Obtener total de aprobadores
        $rolAprobador = \\App\\Models\\Role::where(\'name\', \'aprobador_cotizaciones\')->first();
        $totalAprobadores = $rolAprobador 
            ? \\App\\Models\\User::whereJsonContains(\'roles_ids\', $rolAprobador->id)->count()
            : 0;
        
        return \\view(\'cotizaciones.pendientes\', \\compact(\'cotizaciones\', \'totalAprobadores\'));
    }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"0000000000000c040000000000000000";}}',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'cotizaciones.pendientes',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cotizaciones.obtener-datos' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'cotizaciones/{cotizacion}/datos',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\CotizacionesViewController@getDatosForModal',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\CotizacionesViewController@getDatosForModal',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'cotizaciones.obtener-datos',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cotizaciones.obtener-costos' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'cotizaciones/{cotizacion}/costos',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\ContadorController@obtenerCostos',
        'controller' => 'App\\Http\\Controllers\\ContadorController@obtenerCostos',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'cotizaciones.obtener-costos',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cotizaciones.pendientes-count' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'pendientes-count',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\CotizacionesViewController@cotizacionesPendientesAprobadorCount',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\CotizacionesViewController@cotizacionesPendientesAprobadorCount',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'cotizaciones.pendientes-count',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'aprobador.cotizacion.detail' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'contador/cotizacion/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\ContadorController@getCotizacionDetail',
        'controller' => 'App\\Http\\Controllers\\ContadorController@getCotizacionDetail',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'aprobador.cotizacion.detail',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'aprobador.cotizacion.costos' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'contador/cotizacion/{cotizacion}/costos',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\ContadorController@obtenerCostos',
        'controller' => 'App\\Http\\Controllers\\ContadorController@obtenerCostos',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'aprobador.cotizacion.costos',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cotizacion.pdf' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'cotizacion/{id}/pdf',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\PDFCotizacionController@generarPDF',
        'controller' => 'App\\Http\\Controllers\\PDFCotizacionController@generarPDF',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'cotizacion.pdf',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'contador.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'contador/dashboard',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:contador,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\ContadorController@index',
        'controller' => 'App\\Http\\Controllers\\ContadorController@index',
        'as' => 'contador.index',
        'namespace' => NULL,
        'prefix' => '/contador',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'contador.todas' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'contador/todas',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:contador,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\ContadorController@todas',
        'controller' => 'App\\Http\\Controllers\\ContadorController@todas',
        'as' => 'contador.todas',
        'namespace' => NULL,
        'prefix' => '/contador',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'contador.por-revisar' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'contador/por-revisar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:contador,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\ContadorController@porRevisar',
        'controller' => 'App\\Http\\Controllers\\ContadorController@porRevisar',
        'as' => 'contador.por-revisar',
        'namespace' => NULL,
        'prefix' => '/contador',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'contador.aprobadas' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'contador/aprobadas',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:contador,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\ContadorController@aprobadas',
        'controller' => 'App\\Http\\Controllers\\ContadorController@aprobadas',
        'as' => 'contador.aprobadas',
        'namespace' => NULL,
        'prefix' => '/contador',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'contador.cotizacion-delete' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'contador/cotizacion/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:contador,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\ContadorController@deleteCotizacion',
        'controller' => 'App\\Http\\Controllers\\ContadorController@deleteCotizacion',
        'as' => 'contador.cotizacion-delete',
        'namespace' => NULL,
        'prefix' => '/contador',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'contador.costos.guardar' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'contador/costos/guardar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:contador,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\CostoPrendaController@guardar',
        'controller' => 'App\\Http\\Controllers\\CostoPrendaController@guardar',
        'as' => 'contador.costos.guardar',
        'namespace' => NULL,
        'prefix' => '/contador',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'contador.costos.obtener' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'contador/costos/obtener/{cotizacion_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:contador,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\CostoPrendaController@obtener',
        'controller' => 'App\\Http\\Controllers\\CostoPrendaController@obtener',
        'as' => 'contador.costos.obtener',
        'namespace' => NULL,
        'prefix' => '/contador',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'contador.prenda.guardar-notas-tallas' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'contador/prenda/{prendaId}/notas-tallas',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:contador,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\ContadorController@guardarNotasTallas',
        'controller' => 'App\\Http\\Controllers\\ContadorController@guardarNotasTallas',
        'as' => 'contador.prenda.guardar-notas-tallas',
        'namespace' => NULL,
        'prefix' => '/contador',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'contador.prenda.guardar-texto-personalizado-tallas' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'contador/prenda/{prendaId}/texto-personalizado-tallas',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:contador,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\ContadorController@guardarTextoPersonalizadoTallas',
        'controller' => 'App\\Http\\Controllers\\ContadorController@guardarTextoPersonalizadoTallas',
        'as' => 'contador.prenda.guardar-texto-personalizado-tallas',
        'namespace' => NULL,
        'prefix' => '/contador',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'contador.cotizacion.pdf' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'contador/cotizacion/{id}/pdf',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:contador,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\PDFCotizacionController@generarPDF',
        'controller' => 'App\\Http\\Controllers\\PDFCotizacionController@generarPDF',
        'as' => 'contador.cotizacion.pdf',
        'namespace' => NULL,
        'prefix' => '/contador',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'contador.cotizacion.cambiar-estado' => 
    array (
      'methods' => 
      array (
        0 => 'PATCH',
      ),
      'uri' => 'contador/cotizacion/{id}/estado',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:contador,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\ContadorController@cambiarEstado',
        'controller' => 'App\\Http\\Controllers\\ContadorController@cambiarEstado',
        'as' => 'contador.cotizacion.cambiar-estado',
        'namespace' => NULL,
        'prefix' => '/contador',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'contador.cotizacion.costos' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'contador/cotizacion/{id}/costos',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:contador,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\ContadorController@obtenerCostos',
        'controller' => 'App\\Http\\Controllers\\ContadorController@obtenerCostos',
        'as' => 'contador.cotizacion.costos',
        'namespace' => NULL,
        'prefix' => '/contador',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'contador.tallas-costos.guardar' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'contador/tallas-costos',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:contador,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\ContadorController@guardarTallasCostos',
        'controller' => 'App\\Http\\Controllers\\ContadorController@guardarTallasCostos',
        'as' => 'contador.tallas-costos.guardar',
        'namespace' => NULL,
        'prefix' => '/contador',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'contador.cotizaciones-pendientes-count' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'contador/cotizaciones-pendientes-count',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:contador,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\ContadorController@cotizacionesPendientesCount',
        'controller' => 'App\\Http\\Controllers\\ContadorController@cotizacionesPendientesCount',
        'as' => 'contador.cotizaciones-pendientes-count',
        'namespace' => NULL,
        'prefix' => '/contador',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'contador.profile' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'contador/perfil',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:contador,admin',
          3 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\ContadorController@profile',
        'controller' => 'App\\Http\\Controllers\\ContadorController@profile',
        'as' => 'contador.profile',
        'namespace' => NULL,
        'prefix' => '/contador',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'contador.profile.update' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'contador/perfil/update',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:contador,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\ContadorController@updateProfile',
        'controller' => 'App\\Http\\Controllers\\ContadorController@updateProfile',
        'as' => 'contador.profile.update',
        'namespace' => NULL,
        'prefix' => '/contador',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'operario.dashboard' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'operario/dashboard',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'operario-access',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Operario\\OperarioController@dashboard',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Operario\\OperarioController@dashboard',
        'as' => 'operario.dashboard',
        'namespace' => NULL,
        'prefix' => '/operario',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'operario.mis-pedidos' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'operario/mis-pedidos',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'operario-access',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Operario\\OperarioController@misPedidos',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Operario\\OperarioController@misPedidos',
        'as' => 'operario.mis-pedidos',
        'namespace' => NULL,
        'prefix' => '/operario',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'operario.ver-pedido' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'operario/pedido/{numeroPedido}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'operario-access',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Operario\\OperarioController@verPedido',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Operario\\OperarioController@verPedido',
        'as' => 'operario.ver-pedido',
        'namespace' => NULL,
        'prefix' => '/operario',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'operario.api.pedidos' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'operario/api/pedidos',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'operario-access',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Operario\\OperarioController@obtenerPedidosJson',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Operario\\OperarioController@obtenerPedidosJson',
        'as' => 'operario.api.pedidos',
        'namespace' => NULL,
        'prefix' => '/operario',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'operario.api.pedido' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'operario/api/pedido/{numeroPedido}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'operario-access',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Operario\\OperarioController@obtenerPedidoJson',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Operario\\OperarioController@obtenerPedidoJson',
        'as' => 'operario.api.pedido',
        'namespace' => NULL,
        'prefix' => '/operario',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'operario.api.novedades' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'operario/api/novedades/{numeroPedido}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'operario-access',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Operario\\OperarioController@obtenerNovedades',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Operario\\OperarioController@obtenerNovedades',
        'as' => 'operario.api.novedades',
        'namespace' => NULL,
        'prefix' => '/operario',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'operario.buscar' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'operario/buscar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'operario-access',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Operario\\OperarioController@buscarPedido',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Operario\\OperarioController@buscarPedido',
        'as' => 'operario.buscar',
        'namespace' => NULL,
        'prefix' => '/operario',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'operario.reportar-pendiente' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'operario/reportar-pendiente',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'operario-access',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Operario\\OperarioController@reportarPendiente',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Operario\\OperarioController@reportarPendiente',
        'as' => 'operario.reportar-pendiente',
        'namespace' => NULL,
        'prefix' => '/operario',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'operario.api.completar-proceso' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'operario/api/completar-proceso/{numeroPedido}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'operario-access',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Operario\\OperarioController@completarProceso',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Operario\\OperarioController@completarProceso',
        'as' => 'operario.api.completar-proceso',
        'namespace' => NULL,
        'prefix' => '/operario',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'operario.debug' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'operario/debug',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'operario-access',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Operario\\OperarioController@debug',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Operario\\OperarioController@debug',
        'as' => 'operario.debug',
        'namespace' => NULL,
        'prefix' => '/operario',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'realtime.pedidos.listar' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/realtime/pedidos',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:2071:"function () {
        // Debug: Ver información del usuario y roles
        $user = \\auth()->user();
        
        if (!$user) {
            return \\response()->json([\'error\' => \'No authenticated user\'], 403);
        }
        
        // Debug: Mostrar todos los roles del usuario
        $userRoles = $user->roles->pluck(\'name\')->toArray();
        
        // Verificar si el usuario tiene permisos (verificación manual)
        $hasPermission = $user->hasRole(\'asesor\') || 
                       $user->hasRole(\'admin\') || 
                       $user->hasRole(\'supervisor_pedidos\') || 
                       $user->hasRole(\'despacho\') ||
                       $user->hasRole(\'insumos\');
        
        // Log para debug
        \\Log::info(\'[REALTIME-API] Verificación de permisos\', [
            \'user_id\' => $user->id,
            \'user_roles\' => $userRoles,
            \'has_permission\' => $hasPermission
        ]);
        
        if (!$hasPermission) {
            return \\response()->json([
                \'error\' => \'Unauthorized\',
                \'debug\' => [
                    \'user_id\' => $user->id,
                    \'user_roles\' => $userRoles,
                    \'has_permission\' => $hasPermission
                ]
            ], 403);
        }
        
        // Obtener pedidos según el rol del usuario
        $query = \\App\\Models\\PedidoProduccion::select(\'id\', \'numero_pedido\', \'cliente\', \'estado\', \'area\', \'novedades\', \'forma_de_pago\', \'created_at\', \'fecha_estimada_de_entrega\');
        
        // Si es asesor, solo mostrar sus pedidos
        if ($user->hasRole(\'asesor\')) {
            $query->where(\'asesor_id\', $user->id);
        }
        
        $pedidos = $query->orderBy(\'created_at\', \'desc\')->get();
        
        return \\response()->json([
            \'success\' => true,
            \'data\' => $pedidos->toArray(),
            \'debug\' => [
                \'user_id\' => $user->id,
                \'user_roles\' => $userRoles,
                \'pedidos_count\' => $pedidos->count()
            ]
        ]);
    }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"0000000000000c260000000000000000";}}',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
        'as' => 'realtime.pedidos.listar',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.dashboard' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/dashboard',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\AsesoresController@dashboard',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\AsesoresController@dashboard',
        'as' => 'asesores.dashboard',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.dashboard-data' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/dashboard-data',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\AsesoresController@getDashboardData',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\AsesoresController@getDashboardData',
        'as' => 'asesores.dashboard-data',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.profile' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/perfil',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
          3 => 'auth',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\AsesoresController@profile',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\AsesoresController@profile',
        'as' => 'asesores.profile',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.profile.update' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'asesores/perfil/update',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\AsesoresController@updateProfile',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\AsesoresController@updateProfile',
        'as' => 'asesores.profile.update',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.pedidos.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/pedidos',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\AsesoresController@index',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\AsesoresController@index',
        'as' => 'asesores.pedidos.index',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.pedidos.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/cotizaciones/create',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\AsesoresController@create',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\AsesoresController@create',
        'as' => 'asesores.pedidos.create',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.next-pedido' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/pedidos/next-pedido',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\AsesoresController@getNextPedido',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\AsesoresController@getNextPedido',
        'as' => 'asesores.next-pedido',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.pedidos.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/pedidos/{pedido}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\AsesoresController@show',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\AsesoresController@show',
        'as' => 'asesores.pedidos.show',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.pedidos.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/pedidos/{pedido}/edit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\AsesoresController@edit',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\AsesoresController@edit',
        'as' => 'asesores.pedidos.edit',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.pedidos.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'asesores/pedidos/{pedido}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\AsesoresController@update',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\AsesoresController@update',
        'as' => 'asesores.pedidos.update',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.pedidos.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'asesores/pedidos/{pedido}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\AsesoresController@destroy',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\AsesoresController@destroy',
        'as' => 'asesores.pedidos.destroy',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.pedidos.factura-datos' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/pedidos/{id}/factura-datos',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\AsesoresController@obtenerDatosFactura',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\AsesoresController@obtenerDatosFactura',
        'as' => 'asesores.pedidos.factura-datos',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'id' => '[0-9]+',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.prendas-pedido.fotos' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/prendas-pedido/{prendaPedidoId}/fotos',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\AsesoresController@obtenerFotosPrendaPedido',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\AsesoresController@obtenerFotosPrendaPedido',
        'as' => 'asesores.prendas-pedido.fotos',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'prendaPedidoId' => '[0-9]+',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.pedidos.api.listar' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/pedidos/api/listar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\AsesoresController@apiListar',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\AsesoresController@apiListar',
        'as' => 'asesores.pedidos.api.listar',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.pedidos.anular' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'asesores/pedidos/{id}/anular',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\AsesoresController@anularPedido',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\AsesoresController@anularPedido',
        'as' => 'asesores.pedidos.anular',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'id' => '[0-9]+',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.pedidos.confirmar-correccion' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'asesores/pedidos/{id}/confirmar-correccion',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\AsesoresController@confirmarCorreccion',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\AsesoresController@confirmarCorreccion',
        'as' => 'asesores.pedidos.confirmar-correccion',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'id' => '[0-9]+',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.cotizaciones.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/cotizaciones',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\CotizacionesViewController@index',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\CotizacionesViewController@index',
        'as' => 'asesores.cotizaciones.index',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.cotizaciones.filtros.valores' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/cotizaciones/filtros/valores',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\CotizacionesFiltrosController@valores',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\CotizacionesFiltrosController@valores',
        'as' => 'asesores.cotizaciones.filtros.valores',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.cotizaciones.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'asesores/cotizaciones',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionController@store',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionController@store',
        'as' => 'asesores.cotizaciones.store',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.cotizaciones.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'asesores/cotizaciones/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionController@update',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionController@update',
        'as' => 'asesores.cotizaciones.update',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.cotizacion.pdf.prenda' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/cotizacion/{id}/pdf/prenda',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Http\\Controllers\\PDFPrendaController@generate',
        'controller' => 'App\\Http\\Controllers\\PDFPrendaController@generate',
        'as' => 'asesores.cotizacion.pdf.prenda',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.cotizacion.pdf.combinada' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/cotizacion/{id}/pdf/combinada',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Http\\Controllers\\PDFCotizacionCombiadaController@generate',
        'controller' => 'App\\Http\\Controllers\\PDFCotizacionCombiadaController@generate',
        'as' => 'asesores.cotizacion.pdf.combinada',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.cotizacion.pdf.logo' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/cotizacion/{id}/pdf/logo',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Http\\Controllers\\PDFLogoController@generate',
        'controller' => 'App\\Http\\Controllers\\PDFLogoController@generate',
        'as' => 'asesores.cotizacion.pdf.logo',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.cotizacion.pdf' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/cotizacion/{id}/pdf',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Http\\Controllers\\PDFCotizacionController@generarPDF',
        'controller' => 'App\\Http\\Controllers\\PDFCotizacionController@generarPDF',
        'as' => 'asesores.cotizacion.pdf',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.cotizaciones.imagen.borrar-prenda' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'asesores/cotizaciones/imagenes/prenda/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Cotizaciones\\ImagenBorradorController@borrarPrenda',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Cotizaciones\\ImagenBorradorController@borrarPrenda',
        'as' => 'asesores.cotizaciones.imagen.borrar-prenda',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.cotizaciones.imagen.borrar-tela' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'asesores/cotizaciones/imagenes/tela/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Cotizaciones\\ImagenBorradorController@borrarTela',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Cotizaciones\\ImagenBorradorController@borrarTela',
        'as' => 'asesores.cotizaciones.imagen.borrar-tela',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.cotizaciones.imagen.borrar-logo' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'asesores/cotizaciones/imagenes/logo/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Cotizaciones\\ImagenBorradorController@borrarLogo',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Cotizaciones\\ImagenBorradorController@borrarLogo',
        'as' => 'asesores.cotizaciones.imagen.borrar-logo',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.cotizaciones.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/cotizaciones/{id}/ver',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionController@showView',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionController@showView',
        'as' => 'asesores.cotizaciones.show',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.cotizaciones.get-for-edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/cotizaciones/{id}/editar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionController@getForEdit',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionController@getForEdit',
        'as' => 'asesores.cotizaciones.get-for-edit',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.cotizaciones.api' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/cotizaciones/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionController@show',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionController@show',
        'as' => 'asesores.cotizaciones.api',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.cotizaciones.subir-imagen' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'asesores/cotizaciones/{id}/imagenes',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionController@subirImagen',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionController@subirImagen',
        'as' => 'asesores.cotizaciones.subir-imagen',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.cotizaciones.guardar' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'asesores/cotizaciones/guardar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionController@store',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionController@store',
        'as' => 'asesores.cotizaciones.guardar',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.cotizaciones.edit-borrador' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/cotizaciones/{id}/editar-borrador',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionController@editBorrador',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionController@editBorrador',
        'as' => 'asesores.cotizaciones.edit-borrador',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.cotizaciones.destroy-borrador' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'asesores/cotizaciones/{id}/borrador',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionController@destroyBorrador',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionController@destroyBorrador',
        'as' => 'asesores.cotizaciones.destroy-borrador',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.cotizaciones.anular' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'asesores/cotizaciones/{id}/anular',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionController@anularCotizacion',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionController@anularCotizacion',
        'as' => 'asesores.cotizaciones.anular',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.cotizaciones.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'asesores/cotizaciones/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionController@destroy',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionController@destroy',
        'as' => 'asesores.cotizaciones.destroy',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.pedidos-produccion.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/pedidos-produccion',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionController@index',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionController@index',
        'as' => 'asesores.pedidos-produccion.index',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.pedidos-produccion.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/pedidos-produccion/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionController@show',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionController@show',
        'as' => 'asesores.pedidos-produccion.show',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.pedidos-produccion.obtener-datos-cotizacion' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/pedidos-produccion/obtener-datos-cotizacion/{cotizacionId}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionViewController@obtenerDatosCotizacion',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionViewController@obtenerDatosCotizacion',
        'as' => 'asesores.pedidos-produccion.obtener-datos-cotizacion',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.pedidos-produccion.obtener-prenda-completa' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/pedidos-produccion/obtener-prenda-completa/{cotizacionId}/{prendaId}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionViewController@obtenerPrendaCompleta',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionViewController@obtenerPrendaCompleta',
        'as' => 'asesores.pedidos-produccion.obtener-prenda-completa',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'cotizacionId' => '[0-9]+',
        'prendaId' => '[0-9]+',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.pedidos.prenda-datos' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/pedidos-produccion/{pedidoId}/prenda/{prendaId}/datos',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionController@obtenerDatosPrendaEdicion',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionController@obtenerDatosPrendaEdicion',
        'as' => 'asesores.pedidos.prenda-datos',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'pedidoId' => '[0-9]+',
        'prendaId' => '[0-9]+',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.api.pedidos.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'asesores/api/pedidos',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionController@store',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionController@store',
        'as' => 'asesores.api.pedidos.store',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.api.pedidos.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'asesores/api/pedidos/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionController@update',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionController@update',
        'as' => 'asesores.api.pedidos.update',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.api.pedidos.cambiar-estado' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'asesores/api/pedidos/{id}/estado',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionController@cambiarEstado',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionController@cambiarEstado',
        'as' => 'asesores.api.pedidos.cambiar-estado',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.api.pedidos.agregar-prenda' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'asesores/api/pedidos/{id}/prendas',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionController@agregarPrenda',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionController@agregarPrenda',
        'as' => 'asesores.api.pedidos.agregar-prenda',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.api.pedidos.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'asesores/api/pedidos/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionController@destroy',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionController@destroy',
        'as' => 'asesores.api.pedidos.destroy',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.api.pedidos.filtrar-estado' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/api/pedidos/filtro/estado',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionController@filtrarPorEstado',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionController@filtrarPorEstado',
        'as' => 'asesores.api.pedidos.filtrar-estado',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.api.pedidos.buscar' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/api/pedidos/buscar/{numero}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionController@buscarPorNumero',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionController@buscarPorNumero',
        'as' => 'asesores.api.pedidos.buscar',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.api.pedidos.obtener-prendas' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/api/pedidos/{id}/prendas',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionController@obtenerPrendas',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionController@obtenerPrendas',
        'as' => 'asesores.api.pedidos.obtener-prendas',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.clientes.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/clientes',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Http\\Controllers\\Asesores\\ClientesController@index',
        'controller' => 'App\\Http\\Controllers\\Asesores\\ClientesController@index',
        'as' => 'asesores.clientes.index',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.clientes.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'asesores/clientes',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Http\\Controllers\\Asesores\\ClientesController@store',
        'controller' => 'App\\Http\\Controllers\\Asesores\\ClientesController@store',
        'as' => 'asesores.clientes.store',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.clientes.update' => 
    array (
      'methods' => 
      array (
        0 => 'PATCH',
      ),
      'uri' => 'asesores/clientes/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Http\\Controllers\\Asesores\\ClientesController@update',
        'controller' => 'App\\Http\\Controllers\\Asesores\\ClientesController@update',
        'as' => 'asesores.clientes.update',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.clientes.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'asesores/clientes/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Http\\Controllers\\Asesores\\ClientesController@destroy',
        'controller' => 'App\\Http\\Controllers\\Asesores\\ClientesController@destroy',
        'as' => 'asesores.clientes.destroy',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.reportes.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/reportes',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Http\\Controllers\\Asesores\\ReportesController@index',
        'controller' => 'App\\Http\\Controllers\\Asesores\\ReportesController@index',
        'as' => 'asesores.reportes.index',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.reportes.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'asesores/reportes',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Http\\Controllers\\Asesores\\ReportesController@store',
        'controller' => 'App\\Http\\Controllers\\Asesores\\ReportesController@store',
        'as' => 'asesores.reportes.store',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.reportes.update' => 
    array (
      'methods' => 
      array (
        0 => 'PATCH',
      ),
      'uri' => 'asesores/reportes/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Http\\Controllers\\Asesores\\ReportesController@update',
        'controller' => 'App\\Http\\Controllers\\Asesores\\ReportesController@update',
        'as' => 'asesores.reportes.update',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.reportes.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'asesores/reportes/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Http\\Controllers\\Asesores\\ReportesController@destroy',
        'controller' => 'App\\Http\\Controllers\\Asesores\\ReportesController@destroy',
        'as' => 'asesores.reportes.destroy',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.prendas.agregar' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/prendas/agregar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:77:"function () {
        return \\view(\'asesores.prendas.agregar-prendas\');
    }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"0000000000000c610000000000000000";}}',
        'as' => 'asesores.prendas.agregar',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.cotizaciones-prenda.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/cotizaciones/prenda/crear',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionPrendaController@create',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionPrendaController@create',
        'as' => 'asesores.cotizaciones-prenda.create',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.cotizaciones-prenda.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'asesores/cotizaciones/prenda',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionPrendaController@store',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionPrendaController@store',
        'as' => 'asesores.cotizaciones-prenda.store',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.cotizaciones-prenda.lista' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/cotizaciones/prenda/lista',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionPrendaController@lista',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionPrendaController@lista',
        'as' => 'asesores.cotizaciones-prenda.lista',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.cotizaciones-prenda.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/cotizaciones/prenda/{cotizacion}/editar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionPrendaController@edit',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionPrendaController@edit',
        'as' => 'asesores.cotizaciones-prenda.edit',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.cotizaciones-prenda.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'asesores/cotizaciones/prenda/{cotizacion}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionPrendaController@update',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionPrendaController@update',
        'as' => 'asesores.cotizaciones-prenda.update',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.cotizaciones-prenda.enviar' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'asesores/cotizaciones/prenda/{cotizacion}/enviar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionPrendaController@enviar',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionPrendaController@enviar',
        'as' => 'asesores.cotizaciones-prenda.enviar',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.cotizaciones-prenda.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'asesores/cotizaciones/prenda/{cotizacion}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionPrendaController@destroy',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionPrendaController@destroy',
        'as' => 'asesores.cotizaciones-prenda.destroy',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.cotizaciones-bordado.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/cotizaciones/bordado/crear',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionBordadoController@create',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionBordadoController@create',
        'as' => 'asesores.cotizaciones-bordado.create',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.cotizaciones-bordado.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'asesores/cotizaciones/bordado',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionBordadoController@store',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionBordadoController@store',
        'as' => 'asesores.cotizaciones-bordado.store',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.cotizaciones-bordado.lista' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/cotizaciones/bordado/lista',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionBordadoController@lista',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionBordadoController@lista',
        'as' => 'asesores.cotizaciones-bordado.lista',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.cotizaciones-bordado.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/cotizaciones/bordado/{cotizacion}/editar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionBordadoController@edit',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionBordadoController@edit',
        'as' => 'asesores.cotizaciones-bordado.edit',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.cotizaciones-bordado.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'asesores/cotizaciones/bordado/{cotizacion}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionBordadoController@update',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionBordadoController@update',
        'as' => 'asesores.cotizaciones-bordado.update',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.cotizaciones-bordado.enviar' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'asesores/cotizaciones/bordado/{cotizacion}/enviar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionBordadoController@enviar',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionBordadoController@enviar',
        'as' => 'asesores.cotizaciones-bordado.enviar',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.cotizaciones-bordado.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'asesores/cotizaciones/bordado/{cotizacion}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionBordadoController@destroy',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\CotizacionBordadoController@destroy',
        'as' => 'asesores.cotizaciones-bordado.destroy',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.pedidos.actualizar-prenda-completa' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'asesores/pedidos/{id}/actualizar-prenda',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionController@actualizarPrendaCompleta',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionController@actualizarPrendaCompleta',
        'as' => 'asesores.pedidos.actualizar-prenda-completa',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'id' => '[0-9]+',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.api.tipos-broche-boton' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/api/tipos-broche-boton',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\PedidoController@obtenerTiposBrocheBoton',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\PedidoController@obtenerTiposBrocheBoton',
        'as' => 'asesores.api.tipos-broche-boton',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.api.tipos-manga' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/api/tipos-manga',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\PedidoController@obtenerTiposManga',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\PedidoController@obtenerTiposManga',
        'as' => 'asesores.api.tipos-manga',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.api.tipos-manga.create' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'asesores/api/tipos-manga',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\PedidoController@crearObtenerTipoManga',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\PedidoController@crearObtenerTipoManga',
        'as' => 'asesores.api.tipos-manga.create',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.api.telas' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/api/telas',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\PedidoController@obtenerTelas',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\PedidoController@obtenerTelas',
        'as' => 'asesores.api.telas',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.api.telas.create' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'asesores/api/telas',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\PedidoController@crearObtenerTela',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\PedidoController@crearObtenerTela',
        'as' => 'asesores.api.telas.create',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.api.colores' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/api/colores',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\PedidoController@obtenerColores',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\PedidoController@obtenerColores',
        'as' => 'asesores.api.colores',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.api.colores.create' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'asesores/api/colores',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\PedidoController@crearObtenerColor',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\PedidoController@crearObtenerColor',
        'as' => 'asesores.api.colores.create',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.api.prendas.autocomplete' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/api/prendas/autocomplete',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos,despacho',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\CrearPedidoEditableController@obtenerPrendasAutocomplete',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\CrearPedidoEditableController@obtenerPrendasAutocomplete',
        'as' => 'asesores.api.prendas.autocomplete',
        'namespace' => NULL,
        'prefix' => '/asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.tallas.disponibles' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/tallas-disponibles',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionController@obtenerTallasDisponibles',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionController@obtenerTallasDisponibles',
        'as' => 'api.tallas.disponibles',
        'namespace' => NULL,
        'prefix' => '/api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.prenda.tallas' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/prenda-pedido/{prendaId}/tallas',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionController@obtenerTallasPrenda',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionController@obtenerTallasPrenda',
        'as' => 'api.prenda.tallas',
        'namespace' => NULL,
        'prefix' => '/api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.prenda.variantes' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/prenda-pedido/{prendaId}/variantes',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionController@obtenerVariantesPrenda',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionController@obtenerVariantesPrenda',
        'as' => 'api.prenda.variantes',
        'namespace' => NULL,
        'prefix' => '/api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.prenda.colores-telas' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/prenda-pedido/{prendaId}/colores-telas',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionController@obtenerColoresTelasPrenda',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\PedidosProduccionController@obtenerColoresTelasPrenda',
        'as' => 'api.prenda.colores-telas',
        'namespace' => NULL,
        'prefix' => '/api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.editar' => 
    array (
      'methods' => 
      array (
        0 => 'PATCH',
      ),
      'uri' => 'api/prendas-pedido/{id}/editar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\API\\PrendaPedidoEditController@editPrenda',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\API\\PrendaPedidoEditController@editPrenda',
        'as' => 'api.editar',
        'namespace' => NULL,
        'prefix' => 'api/prendas-pedido',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.editar-campos' => 
    array (
      'methods' => 
      array (
        0 => 'PATCH',
      ),
      'uri' => 'api/prendas-pedido/{id}/editar/campos',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\API\\PrendaPedidoEditController@editPrendaFields',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\API\\PrendaPedidoEditController@editPrendaFields',
        'as' => 'api.editar-campos',
        'namespace' => NULL,
        'prefix' => 'api/prendas-pedido',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.editar-tallas' => 
    array (
      'methods' => 
      array (
        0 => 'PATCH',
      ),
      'uri' => 'api/prendas-pedido/{id}/editar/tallas',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\API\\PrendaPedidoEditController@editTallas',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\API\\PrendaPedidoEditController@editTallas',
        'as' => 'api.editar-tallas',
        'namespace' => NULL,
        'prefix' => 'api/prendas-pedido',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.proceso-actualizar' => 
    array (
      'methods' => 
      array (
        0 => 'PATCH',
        1 => 'POST',
      ),
      'uri' => 'api/prendas-pedido/{prendaId}/procesos/{procesoId}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\API\\PrendaPedidoEditController@actualizarProcesoEspecifico',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\API\\PrendaPedidoEditController@actualizarProcesoEspecifico',
        'as' => 'api.proceso-actualizar',
        'namespace' => NULL,
        'prefix' => 'api/prendas-pedido',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.estado' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/prendas-pedido/{id}/estado',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\API\\PrendaPedidoEditController@getPrendaState',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\API\\PrendaPedidoEditController@getPrendaState',
        'as' => 'api.estado',
        'namespace' => NULL,
        'prefix' => 'api/prendas-pedido',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.variante-editar' => 
    array (
      'methods' => 
      array (
        0 => 'PATCH',
      ),
      'uri' => 'api/prendas-pedido/{prendaId}/variantes/{varianteId}/editar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\API\\PrendaPedidoEditController@editVariante',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\API\\PrendaPedidoEditController@editVariante',
        'as' => 'api.variante-editar',
        'namespace' => NULL,
        'prefix' => 'api/prendas-pedido',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.variante-editar-campos' => 
    array (
      'methods' => 
      array (
        0 => 'PATCH',
      ),
      'uri' => 'api/prendas-pedido/{prendaId}/variantes/{varianteId}/editar/campos',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\API\\PrendaPedidoEditController@editVarianteFields',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\API\\PrendaPedidoEditController@editVarianteFields',
        'as' => 'api.variante-editar-campos',
        'namespace' => NULL,
        'prefix' => 'api/prendas-pedido',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.variante-colores' => 
    array (
      'methods' => 
      array (
        0 => 'PATCH',
      ),
      'uri' => 'api/prendas-pedido/{prendaId}/variantes/{varianteId}/colores',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\API\\PrendaPedidoEditController@editVarianteColores',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\API\\PrendaPedidoEditController@editVarianteColores',
        'as' => 'api.variante-colores',
        'namespace' => NULL,
        'prefix' => 'api/prendas-pedido',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.variante-telas' => 
    array (
      'methods' => 
      array (
        0 => 'PATCH',
      ),
      'uri' => 'api/prendas-pedido/{prendaId}/variantes/{varianteId}/telas',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\API\\PrendaPedidoEditController@editVarianteTelas',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\API\\PrendaPedidoEditController@editVarianteTelas',
        'as' => 'api.variante-telas',
        'namespace' => NULL,
        'prefix' => 'api/prendas-pedido',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.variante-estado' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/prendas-pedido/{prendaId}/variantes/{varianteId}/estado',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\API\\PrendaPedidoEditController@getVarianteState',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\API\\PrendaPedidoEditController@getVarianteState',
        'as' => 'api.variante-estado',
        'namespace' => NULL,
        'prefix' => 'api/prendas-pedido',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.logo-cotizacion-tecnicas.tipos' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/logo-cotizacion-tecnicas/tipos-disponibles',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\LogoCotizacionTecnicaController@tiposDisponibles',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\LogoCotizacionTecnicaController@tiposDisponibles',
        'as' => 'api.logo-cotizacion-tecnicas.tipos',
        'namespace' => NULL,
        'prefix' => '/api/logo-cotizacion-tecnicas',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.logo-cotizacion-tecnicas.agregar' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/logo-cotizacion-tecnicas/agregar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\LogoCotizacionTecnicaController@agregarTecnica',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\LogoCotizacionTecnicaController@agregarTecnica',
        'as' => 'api.logo-cotizacion-tecnicas.agregar',
        'namespace' => NULL,
        'prefix' => '/api/logo-cotizacion-tecnicas',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.logo-cotizacion-tecnicas.obtener' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/logo-cotizacion-tecnicas/cotizacion/{logoCotizacionId}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\LogoCotizacionTecnicaController@obtenerTecnicas',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\LogoCotizacionTecnicaController@obtenerTecnicas',
        'as' => 'api.logo-cotizacion-tecnicas.obtener',
        'namespace' => NULL,
        'prefix' => '/api/logo-cotizacion-tecnicas',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.logo-cotizacion-tecnicas.eliminar' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/logo-cotizacion-tecnicas/{tecnicaId}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\LogoCotizacionTecnicaController@eliminarTecnica',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\LogoCotizacionTecnicaController@eliminarTecnica',
        'as' => 'api.logo-cotizacion-tecnicas.eliminar',
        'namespace' => NULL,
        'prefix' => '/api/logo-cotizacion-tecnicas',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.logo-cotizacion-tecnicas.actualizar-observaciones' => 
    array (
      'methods' => 
      array (
        0 => 'PATCH',
      ),
      'uri' => 'api/logo-cotizacion-tecnicas/{tecnicaId}/observaciones',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\LogoCotizacionTecnicaController@actualizarObservaciones',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\LogoCotizacionTecnicaController@actualizarObservaciones',
        'as' => 'api.logo-cotizacion-tecnicas.actualizar-observaciones',
        'namespace' => NULL,
        'prefix' => '/api/logo-cotizacion-tecnicas',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.logo-cotizacion-tecnicas.prendas' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/logo-cotizacion-tecnicas/prendas',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\LogoCotizacionTecnicaController@obtenerPrendas',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\LogoCotizacionTecnicaController@obtenerPrendas',
        'as' => 'api.logo-cotizacion-tecnicas.prendas',
        'namespace' => NULL,
        'prefix' => '/api/logo-cotizacion-tecnicas',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.logo-cotizacion-tecnicas.guardar-prenda' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/logo-cotizacion-tecnicas/prendas',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\LogoCotizacionTecnicaController@guardarPrenda',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\LogoCotizacionTecnicaController@guardarPrenda',
        'as' => 'api.logo-cotizacion-tecnicas.guardar-prenda',
        'namespace' => NULL,
        'prefix' => '/api/logo-cotizacion-tecnicas',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'supervisor-asesores.dashboard' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'supervisor-asesores/dashboard',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:supervisor_asesores,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\SupervisorAsesoresController@dashboard',
        'controller' => 'App\\Http\\Controllers\\SupervisorAsesoresController@dashboard',
        'as' => 'supervisor-asesores.dashboard',
        'namespace' => NULL,
        'prefix' => '/supervisor-asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'supervisor-asesores.dashboard-stats' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'supervisor-asesores/dashboard-stats',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:supervisor_asesores,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\SupervisorAsesoresController@dashboardStats',
        'controller' => 'App\\Http\\Controllers\\SupervisorAsesoresController@dashboardStats',
        'as' => 'supervisor-asesores.dashboard-stats',
        'namespace' => NULL,
        'prefix' => '/supervisor-asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'supervisor-asesores.cotizaciones.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'supervisor-asesores/cotizaciones',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:supervisor_asesores,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\SupervisorAsesoresController@cotizacionesIndex',
        'controller' => 'App\\Http\\Controllers\\SupervisorAsesoresController@cotizacionesIndex',
        'as' => 'supervisor-asesores.cotizaciones.index',
        'namespace' => NULL,
        'prefix' => '/supervisor-asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'supervisor-asesores.cotizaciones.data' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'supervisor-asesores/cotizaciones/data',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:supervisor_asesores,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\SupervisorAsesoresController@cotizacionesData',
        'controller' => 'App\\Http\\Controllers\\SupervisorAsesoresController@cotizacionesData',
        'as' => 'supervisor-asesores.cotizaciones.data',
        'namespace' => NULL,
        'prefix' => '/supervisor-asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'supervisor-asesores.cotizaciones.filtros.valores' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'supervisor-asesores/cotizaciones/filtros/valores',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:supervisor_asesores,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\SupervisorAsesoresController@cotizacionesFiltrosValores',
        'controller' => 'App\\Http\\Controllers\\SupervisorAsesoresController@cotizacionesFiltrosValores',
        'as' => 'supervisor-asesores.cotizaciones.filtros.valores',
        'namespace' => NULL,
        'prefix' => '/supervisor-asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'supervisor-asesores.pedidos.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'supervisor-asesores/pedidos',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:supervisor_asesores,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\SupervisorAsesoresController@pedidosIndex',
        'controller' => 'App\\Http\\Controllers\\SupervisorAsesoresController@pedidosIndex',
        'as' => 'supervisor-asesores.pedidos.index',
        'namespace' => NULL,
        'prefix' => '/supervisor-asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'supervisor-asesores.pedidos.data' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'supervisor-asesores/pedidos/data',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:supervisor_asesores,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\SupervisorAsesoresController@pedidosData',
        'controller' => 'App\\Http\\Controllers\\SupervisorAsesoresController@pedidosData',
        'as' => 'supervisor-asesores.pedidos.data',
        'namespace' => NULL,
        'prefix' => '/supervisor-asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'supervisor-asesores.pedidos.confirmar-correccion' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'supervisor-asesores/pedidos/{id}/confirmar-correccion',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:supervisor_asesores,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\SupervisorAsesoresController@confirmarCorreccion',
        'controller' => 'App\\Http\\Controllers\\SupervisorAsesoresController@confirmarCorreccion',
        'as' => 'supervisor-asesores.pedidos.confirmar-correccion',
        'namespace' => NULL,
        'prefix' => '/supervisor-asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'supervisor-asesores.asesores.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'supervisor-asesores/asesores',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:supervisor_asesores,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\SupervisorAsesoresController@asesoresIndex',
        'controller' => 'App\\Http\\Controllers\\SupervisorAsesoresController@asesoresIndex',
        'as' => 'supervisor-asesores.asesores.index',
        'namespace' => NULL,
        'prefix' => '/supervisor-asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'supervisor-asesores.asesores.data' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'supervisor-asesores/asesores/data',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:supervisor_asesores,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\SupervisorAsesoresController@asesoresData',
        'controller' => 'App\\Http\\Controllers\\SupervisorAsesoresController@asesoresData',
        'as' => 'supervisor-asesores.asesores.data',
        'namespace' => NULL,
        'prefix' => '/supervisor-asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'supervisor-asesores.asesores.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'supervisor-asesores/asesores/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:supervisor_asesores,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\SupervisorAsesoresController@asesoresShow',
        'controller' => 'App\\Http\\Controllers\\SupervisorAsesoresController@asesoresShow',
        'as' => 'supervisor-asesores.asesores.show',
        'namespace' => NULL,
        'prefix' => '/supervisor-asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'supervisor-asesores.reportes.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'supervisor-asesores/reportes',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:supervisor_asesores,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\SupervisorAsesoresController@reportesIndex',
        'controller' => 'App\\Http\\Controllers\\SupervisorAsesoresController@reportesIndex',
        'as' => 'supervisor-asesores.reportes.index',
        'namespace' => NULL,
        'prefix' => '/supervisor-asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'supervisor-asesores.reportes.data' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'supervisor-asesores/reportes/data',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:supervisor_asesores,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\SupervisorAsesoresController@reportesData',
        'controller' => 'App\\Http\\Controllers\\SupervisorAsesoresController@reportesData',
        'as' => 'supervisor-asesores.reportes.data',
        'namespace' => NULL,
        'prefix' => '/supervisor-asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'supervisor-asesores.profile.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'supervisor-asesores/perfil',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:supervisor_asesores,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\SupervisorAsesoresController@profileIndex',
        'controller' => 'App\\Http\\Controllers\\SupervisorAsesoresController@profileIndex',
        'as' => 'supervisor-asesores.profile.index',
        'namespace' => NULL,
        'prefix' => '/supervisor-asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'supervisor-asesores.profile.stats' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'supervisor-asesores/perfil/stats',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:supervisor_asesores,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\SupervisorAsesoresController@profileStats',
        'controller' => 'App\\Http\\Controllers\\SupervisorAsesoresController@profileStats',
        'as' => 'supervisor-asesores.profile.stats',
        'namespace' => NULL,
        'prefix' => '/supervisor-asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'supervisor-asesores.profile.password-update' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'supervisor-asesores/perfil/password-update',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:supervisor_asesores,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\SupervisorAsesoresController@profilePasswordUpdate',
        'controller' => 'App\\Http\\Controllers\\SupervisorAsesoresController@profilePasswordUpdate',
        'as' => 'supervisor-asesores.profile.password-update',
        'namespace' => NULL,
        'prefix' => '/supervisor-asesores',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'visualizador-logo.dashboard' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'visualizador-logo/dashboard',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:visualizador_cotizaciones_logo,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\VisualizadorLogoController@dashboard',
        'controller' => 'App\\Http\\Controllers\\VisualizadorLogoController@dashboard',
        'as' => 'visualizador-logo.dashboard',
        'namespace' => NULL,
        'prefix' => '/visualizador-logo',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'visualizador-logo.cotizaciones' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'visualizador-logo/cotizaciones',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:visualizador_cotizaciones_logo,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\VisualizadorLogoController@getCotizaciones',
        'controller' => 'App\\Http\\Controllers\\VisualizadorLogoController@getCotizaciones',
        'as' => 'visualizador-logo.cotizaciones',
        'namespace' => NULL,
        'prefix' => '/visualizador-logo',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'visualizador-logo.cotizaciones.ver' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'visualizador-logo/cotizaciones/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:visualizador_cotizaciones_logo,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\VisualizadorLogoController@verCotizacion',
        'controller' => 'App\\Http\\Controllers\\VisualizadorLogoController@verCotizacion',
        'as' => 'visualizador-logo.cotizaciones.ver',
        'namespace' => NULL,
        'prefix' => '/visualizador-logo',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'visualizador-logo.estadisticas' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'visualizador-logo/estadisticas',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:visualizador_cotizaciones_logo,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\VisualizadorLogoController@getEstadisticas',
        'controller' => 'App\\Http\\Controllers\\VisualizadorLogoController@getEstadisticas',
        'as' => 'visualizador-logo.estadisticas',
        'namespace' => NULL,
        'prefix' => '/visualizador-logo',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'visualizador-logo.cotizaciones.pdf-logo' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'visualizador-logo/cotizaciones/{id}/pdf-logo',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:visualizador_cotizaciones_logo,admin',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:117:"function($id) {
        return \\redirect()->route(\'pdf.cotizacion\', [\'cotizacionId\' => $id, \'tipo\' => \'logo\']);
    }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"0000000000000cc30000000000000000";}}',
        'as' => 'visualizador-logo.cotizaciones.pdf-logo',
        'namespace' => NULL,
        'prefix' => '/visualizador-logo',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'debug.registros-performance' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'debug/registros/performance',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:admin',
        ),
        'uses' => 'App\\Http\\Controllers\\DebugRegistrosController@debugPerformance',
        'controller' => 'App\\Http\\Controllers\\DebugRegistrosController@debugPerformance',
        'as' => 'debug.registros-performance',
        'namespace' => NULL,
        'prefix' => '/debug',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'debug.registros-queries' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'debug/registros/queries',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:admin',
        ),
        'uses' => 'App\\Http\\Controllers\\DebugRegistrosController@listAllQueries',
        'controller' => 'App\\Http\\Controllers\\DebugRegistrosController@listAllQueries',
        'as' => 'debug.registros-queries',
        'namespace' => NULL,
        'prefix' => '/debug',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'debug.registros-table-analysis' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'debug/registros/table-analysis',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:admin',
        ),
        'uses' => 'App\\Http\\Controllers\\DebugRegistrosController@analyzeTable',
        'controller' => 'App\\Http\\Controllers\\DebugRegistrosController@analyzeTable',
        'as' => 'debug.registros-table-analysis',
        'namespace' => NULL,
        'prefix' => '/debug',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'debug.registros-suggest-indices' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'debug/registros/suggest-indices',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:admin',
        ),
        'uses' => 'App\\Http\\Controllers\\DebugRegistrosController@suggestIndices',
        'controller' => 'App\\Http\\Controllers\\DebugRegistrosController@suggestIndices',
        'as' => 'debug.registros-suggest-indices',
        'namespace' => NULL,
        'prefix' => '/debug',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'inventario-telas.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'inventario-telas',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\AsesoresInventarioTelasController@index',
        'controller' => 'App\\Http\\Controllers\\AsesoresInventarioTelasController@index',
        'as' => 'inventario-telas.index',
        'namespace' => NULL,
        'prefix' => '/inventario-telas',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'inventario-telas.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'inventario-telas/store',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\AsesoresInventarioTelasController@store',
        'controller' => 'App\\Http\\Controllers\\AsesoresInventarioTelasController@store',
        'as' => 'inventario-telas.store',
        'namespace' => NULL,
        'prefix' => '/inventario-telas',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'inventario-telas.ajustar-stock' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'inventario-telas/ajustar-stock',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\AsesoresInventarioTelasController@ajustarStock',
        'controller' => 'App\\Http\\Controllers\\AsesoresInventarioTelasController@ajustarStock',
        'as' => 'inventario-telas.ajustar-stock',
        'namespace' => NULL,
        'prefix' => '/inventario-telas',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'inventario-telas.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'inventario-telas/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\AsesoresInventarioTelasController@destroy',
        'controller' => 'App\\Http\\Controllers\\AsesoresInventarioTelasController@destroy',
        'as' => 'inventario-telas.destroy',
        'namespace' => NULL,
        'prefix' => '/inventario-telas',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'inventario-telas.historial' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'inventario-telas/historial',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\AsesoresInventarioTelasController@historial',
        'controller' => 'App\\Http\\Controllers\\AsesoresInventarioTelasController@historial',
        'as' => 'inventario-telas.historial',
        'namespace' => NULL,
        'prefix' => '/inventario-telas',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.tipos-prenda' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/tipos-prenda',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\PrendaController@tiposPrenda',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\PrendaController@tiposPrenda',
        'as' => 'api.tipos-prenda',
        'namespace' => NULL,
        'prefix' => '/api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.prenda.reconocer' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/prenda/reconocer',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\PrendaController@reconocer',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\PrendaController@reconocer',
        'as' => 'api.prenda.reconocer',
        'namespace' => NULL,
        'prefix' => '/api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'prenda-variaciones' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'prenda-variaciones/{tipoPrendaId}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:224:"function($tipoPrendaId) {
    // Por ahora retornar vacío ya que el sistema maneja las variaciones automáticamente
    // El frontend espera null cuando no hay variaciones predefinidas
    return \\response()->json(null);
}";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"0000000000000b5b0000000000000000";}}',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'prenda-variaciones',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'insumos.dashboard' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'insumos/dashboard',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'insumos-access',
        ),
        'uses' => 'App\\Http\\Controllers\\Insumos\\InsumosController@dashboard',
        'controller' => 'App\\Http\\Controllers\\Insumos\\InsumosController@dashboard',
        'as' => 'insumos.dashboard',
        'namespace' => NULL,
        'prefix' => '/insumos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'insumos.materiales.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'insumos/materiales',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'insumos-access',
        ),
        'uses' => 'App\\Http\\Controllers\\Insumos\\InsumosController@materiales',
        'controller' => 'App\\Http\\Controllers\\Insumos\\InsumosController@materiales',
        'as' => 'insumos.materiales.index',
        'namespace' => NULL,
        'prefix' => '/insumos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'insumos.materiales.guardar' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'insumos/materiales/{pedido}/guardar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'insumos-access',
        ),
        'uses' => 'App\\Http\\Controllers\\Insumos\\InsumosController@guardarMateriales',
        'controller' => 'App\\Http\\Controllers\\Insumos\\InsumosController@guardarMateriales',
        'as' => 'insumos.materiales.guardar',
        'namespace' => NULL,
        'prefix' => '/insumos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'insumos.materiales.eliminar' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'insumos/materiales/{pedido}/eliminar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'insumos-access',
        ),
        'uses' => 'App\\Http\\Controllers\\Insumos\\InsumosController@eliminarMaterial',
        'controller' => 'App\\Http\\Controllers\\Insumos\\InsumosController@eliminarMaterial',
        'as' => 'insumos.materiales.eliminar',
        'namespace' => NULL,
        'prefix' => '/insumos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'insumos.materiales.guardar-ancho-metraje' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'insumos/materiales/{numeroPedido}/guardar-ancho-metraje',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'insumos-access',
        ),
        'uses' => 'App\\Http\\Controllers\\Insumos\\InsumosController@guardarAnchoMetraje',
        'controller' => 'App\\Http\\Controllers\\Insumos\\InsumosController@guardarAnchoMetraje',
        'as' => 'insumos.materiales.guardar-ancho-metraje',
        'namespace' => NULL,
        'prefix' => '/insumos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'insumos.materiales.obtener-ancho-metraje' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'insumos/materiales/{numeroPedido}/obtener-ancho-metraje',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'insumos-access',
        ),
        'uses' => 'App\\Http\\Controllers\\Insumos\\InsumosController@obtenerAnchoMetraje',
        'controller' => 'App\\Http\\Controllers\\Insumos\\InsumosController@obtenerAnchoMetraje',
        'as' => 'insumos.materiales.obtener-ancho-metraje',
        'namespace' => NULL,
        'prefix' => '/insumos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'insumos.materiales.obtener-prendas' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'insumos/materiales/{numeroPedido}/obtener-prendas',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'insumos-access',
        ),
        'uses' => 'App\\Http\\Controllers\\Insumos\\InsumosController@obtenerPrendas',
        'controller' => 'App\\Http\\Controllers\\Insumos\\InsumosController@obtenerPrendas',
        'as' => 'insumos.materiales.obtener-prendas',
        'namespace' => NULL,
        'prefix' => '/insumos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'insumos.materiales.obtener-ancho-metraje-prenda' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'insumos/materiales/{numeroPedido}/obtener-ancho-metraje-prenda/{prendaId}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'insumos-access',
        ),
        'uses' => 'App\\Http\\Controllers\\Insumos\\InsumosController@obtenerAnchoMetrajePrenda',
        'controller' => 'App\\Http\\Controllers\\Insumos\\InsumosController@obtenerAnchoMetrajePrenda',
        'as' => 'insumos.materiales.obtener-ancho-metraje-prenda',
        'namespace' => NULL,
        'prefix' => '/insumos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'insumos.materiales.guardar-ancho-metraje-prenda' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'insumos/materiales/{numeroPedido}/guardar-ancho-metraje-prenda',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'insumos-access',
        ),
        'uses' => 'App\\Http\\Controllers\\Insumos\\InsumosController@guardarAnchoMetrajePrenda',
        'controller' => 'App\\Http\\Controllers\\Insumos\\InsumosController@guardarAnchoMetrajePrenda',
        'as' => 'insumos.materiales.guardar-ancho-metraje-prenda',
        'namespace' => NULL,
        'prefix' => '/insumos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'insumos.api.materiales' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'insumos/api/materiales/{pedido}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'insumos-access',
        ),
        'uses' => 'App\\Http\\Controllers\\Insumos\\InsumosController@obtenerMateriales',
        'controller' => 'App\\Http\\Controllers\\Insumos\\InsumosController@obtenerMateriales',
        'as' => 'insumos.api.materiales',
        'namespace' => NULL,
        'prefix' => '/insumos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'insumos.api.filtros' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'insumos/api/filtros/{column}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'insumos-access',
        ),
        'uses' => 'App\\Http\\Controllers\\Insumos\\InsumosController@obtenerValoresFiltro',
        'controller' => 'App\\Http\\Controllers\\Insumos\\InsumosController@obtenerValoresFiltro',
        'as' => 'insumos.api.filtros',
        'namespace' => NULL,
        'prefix' => '/insumos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'insumos.materiales.cambiar-estado' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'insumos/materiales/{numeroPedido}/cambiar-estado',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'insumos-access',
        ),
        'uses' => 'App\\Http\\Controllers\\Insumos\\InsumosController@cambiarEstado',
        'controller' => 'App\\Http\\Controllers\\Insumos\\InsumosController@cambiarEstado',
        'as' => 'insumos.materiales.cambiar-estado',
        'namespace' => NULL,
        'prefix' => '/insumos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'insumos.test' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'insumos/test',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'insumos-access',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:57:"function () {
        return \\view(\'insumos.test\');
    }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"0000000000000cde0000000000000000";}}',
        'as' => 'insumos.test',
        'namespace' => NULL,
        'prefix' => '/insumos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'insumos.metrajes.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'insumos/metrajes',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'insumos-access',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:67:"function () {
        return \\view(\'insumos.metrajes.index\');
    }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"0000000000000ce00000000000000000";}}',
        'as' => 'insumos.metrajes.index',
        'namespace' => NULL,
        'prefix' => '/insumos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'supervisor-pedidos.datos' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'supervisor-pedidos/{id}/datos',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,supervisor_pedidos,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\SupervisorPedidosController@obtenerDatos',
        'controller' => 'App\\Http\\Controllers\\SupervisorPedidosController@obtenerDatos',
        'as' => 'supervisor-pedidos.datos',
        'namespace' => NULL,
        'prefix' => '/supervisor-pedidos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'supervisor-pedidos.factura-datos' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'supervisor-pedidos/{id}/factura-datos',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,supervisor_pedidos,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\SupervisorPedidosController@obtenerDatosFactura',
        'controller' => 'App\\Http\\Controllers\\SupervisorPedidosController@obtenerDatosFactura',
        'as' => 'supervisor-pedidos.factura-datos',
        'namespace' => NULL,
        'prefix' => '/supervisor-pedidos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'supervisor-pedidos.comparar' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'supervisor-pedidos/{id}/comparar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,supervisor_pedidos,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\SupervisorPedidosController@obtenerDatosComparacion',
        'controller' => 'App\\Http\\Controllers\\SupervisorPedidosController@obtenerDatosComparacion',
        'as' => 'supervisor-pedidos.comparar',
        'namespace' => NULL,
        'prefix' => '/supervisor-pedidos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'supervisor-pedidos.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'supervisor-pedidos',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:supervisor_pedidos,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\SupervisorPedidosController@index',
        'controller' => 'App\\Http\\Controllers\\SupervisorPedidosController@index',
        'as' => 'supervisor-pedidos.index',
        'namespace' => NULL,
        'prefix' => '/supervisor-pedidos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'supervisor-pedidos.profile' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'supervisor-pedidos/perfil/editar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:supervisor_pedidos,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\SupervisorPedidosController@profile',
        'controller' => 'App\\Http\\Controllers\\SupervisorPedidosController@profile',
        'as' => 'supervisor-pedidos.profile',
        'namespace' => NULL,
        'prefix' => '/supervisor-pedidos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'supervisor-pedidos.update-profile' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'supervisor-pedidos/perfil/actualizar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:supervisor_pedidos,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\SupervisorPedidosController@updateProfile',
        'controller' => 'App\\Http\\Controllers\\SupervisorPedidosController@updateProfile',
        'as' => 'supervisor-pedidos.update-profile',
        'namespace' => NULL,
        'prefix' => '/supervisor-pedidos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'supervisor-pedidos.notifications' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'supervisor-pedidos/notificaciones',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:supervisor_pedidos,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\SupervisorPedidosController@getNotifications',
        'controller' => 'App\\Http\\Controllers\\SupervisorPedidosController@getNotifications',
        'as' => 'supervisor-pedidos.notifications',
        'namespace' => NULL,
        'prefix' => '/supervisor-pedidos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'supervisor-pedidos.mark-all-read' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'supervisor-pedidos/notificaciones/marcar-todas-leidas',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:supervisor_pedidos,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\SupervisorPedidosController@markAllNotificationsAsRead',
        'controller' => 'App\\Http\\Controllers\\SupervisorPedidosController@markAllNotificationsAsRead',
        'as' => 'supervisor-pedidos.mark-all-read',
        'namespace' => NULL,
        'prefix' => '/supervisor-pedidos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'supervisor-pedidos.mark-read' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'supervisor-pedidos/notificaciones/{notificationId}/marcar-leida',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:supervisor_pedidos,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\SupervisorPedidosController@markNotificationAsRead',
        'controller' => 'App\\Http\\Controllers\\SupervisorPedidosController@markNotificationAsRead',
        'as' => 'supervisor-pedidos.mark-read',
        'namespace' => NULL,
        'prefix' => '/supervisor-pedidos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'supervisor-pedidos.filtro-opciones' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'supervisor-pedidos/filtro-opciones/{campo}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:supervisor_pedidos,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\SupervisorPedidosController@obtenerOpcionesFiltro',
        'controller' => 'App\\Http\\Controllers\\SupervisorPedidosController@obtenerOpcionesFiltro',
        'as' => 'supervisor-pedidos.filtro-opciones',
        'namespace' => NULL,
        'prefix' => '/supervisor-pedidos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'supervisor-pedidos.ordenes-pendientes-count' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'supervisor-pedidos/ordenes-pendientes-count',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:supervisor_pedidos,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\SupervisorPedidosController@ordenesPendientesCount',
        'controller' => 'App\\Http\\Controllers\\SupervisorPedidosController@ordenesPendientesCount',
        'as' => 'supervisor-pedidos.ordenes-pendientes-count',
        'namespace' => NULL,
        'prefix' => '/supervisor-pedidos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'supervisor-pedidos.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'supervisor-pedidos/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:supervisor_pedidos,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\SupervisorPedidosController@show',
        'controller' => 'App\\Http\\Controllers\\SupervisorPedidosController@show',
        'as' => 'supervisor-pedidos.show',
        'namespace' => NULL,
        'prefix' => '/supervisor-pedidos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'supervisor-pedidos.pdf' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'supervisor-pedidos/{id}/pdf',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:supervisor_pedidos,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\SupervisorPedidosController@descargarPDF',
        'controller' => 'App\\Http\\Controllers\\SupervisorPedidosController@descargarPDF',
        'as' => 'supervisor-pedidos.pdf',
        'namespace' => NULL,
        'prefix' => '/supervisor-pedidos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'supervisor-pedidos.anular' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'supervisor-pedidos/{id}/anular',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:supervisor_pedidos,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\SupervisorPedidosController@anular',
        'controller' => 'App\\Http\\Controllers\\SupervisorPedidosController@anular',
        'as' => 'supervisor-pedidos.anular',
        'namespace' => NULL,
        'prefix' => '/supervisor-pedidos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'supervisor-pedidos.aprobar' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'supervisor-pedidos/{id}/aprobar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:supervisor_pedidos,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\SupervisorPedidosController@aprobar',
        'controller' => 'App\\Http\\Controllers\\SupervisorPedidosController@aprobar',
        'as' => 'supervisor-pedidos.aprobar',
        'namespace' => NULL,
        'prefix' => '/supervisor-pedidos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'supervisor-pedidos.cambiar-estado' => 
    array (
      'methods' => 
      array (
        0 => 'PATCH',
      ),
      'uri' => 'supervisor-pedidos/{id}/estado',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:supervisor_pedidos,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\SupervisorPedidosController@cambiarEstado',
        'controller' => 'App\\Http\\Controllers\\SupervisorPedidosController@cambiarEstado',
        'as' => 'supervisor-pedidos.cambiar-estado',
        'namespace' => NULL,
        'prefix' => '/supervisor-pedidos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'supervisor-pedidos.editar' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'supervisor-pedidos/{id}/editar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:supervisor_pedidos,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\SupervisorPedidosController@edit',
        'controller' => 'App\\Http\\Controllers\\SupervisorPedidosController@edit',
        'as' => 'supervisor-pedidos.editar',
        'namespace' => NULL,
        'prefix' => '/supervisor-pedidos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'supervisor-pedidos.actualizar' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'supervisor-pedidos/{id}/actualizar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:supervisor_pedidos,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\SupervisorPedidosController@update',
        'controller' => 'App\\Http\\Controllers\\SupervisorPedidosController@update',
        'as' => 'supervisor-pedidos.actualizar',
        'namespace' => NULL,
        'prefix' => '/supervisor-pedidos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'supervisor-pedidos.actualizar.post' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'supervisor-pedidos/{id}/actualizar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:supervisor_pedidos,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\SupervisorPedidosController@update',
        'controller' => 'App\\Http\\Controllers\\SupervisorPedidosController@update',
        'as' => 'supervisor-pedidos.actualizar.post',
        'namespace' => NULL,
        'prefix' => '/supervisor-pedidos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'supervisor-pedidos.imagen.eliminar' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'supervisor-pedidos/imagen/{tipo}/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:supervisor_pedidos,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\SupervisorPedidosController@deleteImage',
        'controller' => 'App\\Http\\Controllers\\SupervisorPedidosController@deleteImage',
        'as' => 'supervisor-pedidos.imagen.eliminar',
        'namespace' => NULL,
        'prefix' => '/supervisor-pedidos',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'bordado.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'bordado',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:bordado,admin',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:58:"function () {
        return \\view(\'bordado.index\');
    }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"0000000000000cf60000000000000000";}}',
        'as' => 'bordado.index',
        'namespace' => NULL,
        'prefix' => '/bordado',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'bordado.cotizaciones' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'bordado/cotizaciones',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:bordado,admin',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:84:"function () {
        return \\redirect()->route(\'bordado.cotizaciones.lista\');
    }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"0000000000000cf80000000000000000";}}',
        'as' => 'bordado.cotizaciones',
        'namespace' => NULL,
        'prefix' => '/bordado',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'bordado.cotizaciones.lista' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'bordado/cotizaciones/lista',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:bordado,admin',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:79:"function () {
            return \\view(\'bordado.cotizaciones.lista\');
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"0000000000000cfc0000000000000000";}}',
        'as' => 'bordado.cotizaciones.lista',
        'namespace' => NULL,
        'prefix' => 'bordado/cotizaciones',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'bordado.cotizaciones.medidas' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'bordado/cotizaciones/medidas',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:bordado,admin',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:81:"function () {
            return \\view(\'bordado.cotizaciones.medidas\');
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"0000000000000cfe0000000000000000";}}',
        'as' => 'bordado.cotizaciones.medidas',
        'namespace' => NULL,
        'prefix' => 'bordado/cotizaciones',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.festivos.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/festivos',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\FestivosController@index',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\FestivosController@index',
        'as' => 'api.festivos.index',
        'namespace' => NULL,
        'prefix' => '/api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.festivos.detailed' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/festivos/detailed',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\FestivosController@detailed',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\FestivosController@detailed',
        'as' => 'api.festivos.detailed',
        'namespace' => NULL,
        'prefix' => '/api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.festivos.check' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/festivos/check',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\FestivosController@check',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\FestivosController@check',
        'as' => 'api.festivos.check',
        'namespace' => NULL,
        'prefix' => '/api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.festivos.range' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/festivos/range',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\FestivosController@range',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\FestivosController@range',
        'as' => 'api.festivos.range',
        'namespace' => NULL,
        'prefix' => '/api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cotizaciones.estado.enviar' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'cotizaciones/{cotizacion}/enviar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'App\\Http\\Controllers\\CotizacionEstadoController@enviar',
        'controller' => 'App\\Http\\Controllers\\CotizacionEstadoController@enviar',
        'as' => 'cotizaciones.estado.enviar',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cotizaciones.estado.aprobar-contador' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'cotizaciones/{cotizacion}/aprobar-contador',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'App\\Http\\Controllers\\CotizacionEstadoController@aprobarContador',
        'controller' => 'App\\Http\\Controllers\\CotizacionEstadoController@aprobarContador',
        'as' => 'cotizaciones.estado.aprobar-contador',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cotizaciones.estado.aprobar-para-pedido' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'cotizaciones/{cotizacion}/aprobar-para-pedido',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'App\\Http\\Controllers\\CotizacionEstadoController@aprobarParaPedido',
        'controller' => 'App\\Http\\Controllers\\CotizacionEstadoController@aprobarParaPedido',
        'as' => 'cotizaciones.estado.aprobar-para-pedido',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cotizaciones.estado.aprobar-aprobador' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'cotizaciones/{cotizacion}/aprobar-aprobador',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'App\\Http\\Controllers\\CotizacionEstadoController@aprobarAprobador',
        'controller' => 'App\\Http\\Controllers\\CotizacionEstadoController@aprobarAprobador',
        'as' => 'cotizaciones.estado.aprobar-aprobador',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cotizaciones.estado.rechazar' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'cotizaciones/{cotizacion}/rechazar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'App\\Http\\Controllers\\CotizacionEstadoController@rechazar',
        'controller' => 'App\\Http\\Controllers\\CotizacionEstadoController@rechazar',
        'as' => 'cotizaciones.estado.rechazar',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cotizaciones.estado.historial' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'cotizaciones/{cotizacion}/historial',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'App\\Http\\Controllers\\CotizacionEstadoController@historial',
        'controller' => 'App\\Http\\Controllers\\CotizacionEstadoController@historial',
        'as' => 'cotizaciones.estado.historial',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cotizaciones.estado.seguimiento' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'cotizaciones/{cotizacion}/seguimiento',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'App\\Http\\Controllers\\CotizacionEstadoController@seguimiento',
        'controller' => 'App\\Http\\Controllers\\CotizacionEstadoController@seguimiento',
        'as' => 'cotizaciones.estado.seguimiento',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'pedidos.estado.aprobar-supervisor' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'pedidos/{pedido}/aprobar-supervisor',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'App\\Http\\Controllers\\PedidoEstadoController@aprobarSupervisor',
        'controller' => 'App\\Http\\Controllers\\PedidoEstadoController@aprobarSupervisor',
        'as' => 'pedidos.estado.aprobar-supervisor',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'pedidos.estado.historial' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'pedidos/{pedido}/historial',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'App\\Http\\Controllers\\PedidoEstadoController@historial',
        'controller' => 'App\\Http\\Controllers\\PedidoEstadoController@historial',
        'as' => 'pedidos.estado.historial',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'pedidos.estado.seguimiento' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'pedidos/{pedido}/seguimiento',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'App\\Http\\Controllers\\PedidoEstadoController@seguimiento',
        'controller' => 'App\\Http\\Controllers\\PedidoEstadoController@seguimiento',
        'as' => 'pedidos.estado.seguimiento',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'storage.serve' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'storage-serve/{path}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:118:"function($path) {
    $path = \\str_replace(\'..\', \'\', $path);
    return \\redirect(\'/storage/\' . \\ltrim($path, \'/\'));
}";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"0000000000000b5a0000000000000000";}}',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'storage.serve',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'path' => '.*',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asistencia-personal.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asistencia-personal',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\AsistenciaPersonalWebController@index',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\AsistenciaPersonalWebController@index',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'asistencia-personal.index',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asistencia-personal.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asistencia-personal/crear',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\AsistenciaPersonalWebController@create',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\AsistenciaPersonalWebController@create',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'asistencia-personal.create',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asistencia-personal.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'asistencia-personal',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\AsistenciaPersonalWebController@store',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\AsistenciaPersonalWebController@store',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'asistencia-personal.store',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asistencia-personal.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asistencia-personal/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\AsistenciaPersonalWebController@show',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\AsistenciaPersonalWebController@show',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'asistencia-personal.show',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asistencia-personal.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asistencia-personal/{id}/editar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\AsistenciaPersonalWebController@edit',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\AsistenciaPersonalWebController@edit',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'asistencia-personal.edit',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asistencia-personal.update' => 
    array (
      'methods' => 
      array (
        0 => 'PATCH',
      ),
      'uri' => 'asistencia-personal/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\AsistenciaPersonalWebController@update',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\AsistenciaPersonalWebController@update',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'asistencia-personal.update',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asistencia-personal.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'asistencia-personal/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\AsistenciaPersonalWebController@destroy',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\AsistenciaPersonalWebController@destroy',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'asistencia-personal.destroy',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asistencia-personal.procesar-pdf' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'asistencia-personal/procesar-pdf',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\AsistenciaPersonalController@procesarPDF',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\AsistenciaPersonalController@procesarPDF',
        'as' => 'asistencia-personal.procesar-pdf',
        'namespace' => NULL,
        'prefix' => '/asistencia-personal',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asistencia-personal.validar-registros' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'asistencia-personal/validar-registros',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\AsistenciaPersonalController@validarRegistros',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\AsistenciaPersonalController@validarRegistros',
        'as' => 'asistencia-personal.validar-registros',
        'namespace' => NULL,
        'prefix' => '/asistencia-personal',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asistencia-personal.guardar-registros' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'asistencia-personal/guardar-registros',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\AsistenciaPersonalController@guardarRegistros',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\AsistenciaPersonalController@guardarRegistros',
        'as' => 'asistencia-personal.guardar-registros',
        'namespace' => NULL,
        'prefix' => '/asistencia-personal',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asistencia-personal.calcular-horas' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'asistencia-personal/calcular-horas',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\AsistenciaPersonalController@calcularHoras',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\AsistenciaPersonalController@calcularHoras',
        'as' => 'asistencia-personal.calcular-horas',
        'namespace' => NULL,
        'prefix' => '/asistencia-personal',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asistencia-personal.reportes.detalles' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asistencia-personal/reportes/{id}/detalles',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\AsistenciaPersonalController@getReportDetails',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\AsistenciaPersonalController@getReportDetails',
        'as' => 'asistencia-personal.reportes.detalles',
        'namespace' => NULL,
        'prefix' => '/asistencia-personal',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asistencia-personal.reportes.ausencias' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asistencia-personal/reportes/{id}/ausencias',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\AsistenciaPersonalController@getAbsenciasDelDia',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\AsistenciaPersonalController@getAbsenciasDelDia',
        'as' => 'asistencia-personal.reportes.ausencias',
        'namespace' => NULL,
        'prefix' => '/asistencia-personal',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asistencia-personal.guardar-asistencia-detallada' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'asistencia-personal/guardar-asistencia-detallada',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\AsistenciaDetalladaController@guardarCambios',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\AsistenciaDetalladaController@guardarCambios',
        'as' => 'asistencia-personal.guardar-asistencia-detallada',
        'namespace' => NULL,
        'prefix' => '/asistencia-personal',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asistencia-personal.guardar-hora-extra-agregada' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'asistencia-personal/guardar-hora-extra-agregada',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\AsistenciaPersonalController@guardarHoraExtraAgregada',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\AsistenciaPersonalController@guardarHoraExtraAgregada',
        'as' => 'asistencia-personal.guardar-hora-extra-agregada',
        'namespace' => NULL,
        'prefix' => '/asistencia-personal',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asistencia-personal.guardar-marcas-editadas' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'asistencia-personal/guardar-marcas-editadas',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\AsistenciaPersonalController@guardarMarcasEditadas',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\AsistenciaPersonalController@guardarMarcasEditadas',
        'as' => 'asistencia-personal.guardar-marcas-editadas',
        'namespace' => NULL,
        'prefix' => '/asistencia-personal',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asistencia-personal.agregar-marca-faltante' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'asistencia-personal/agregar-marca-faltante',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\AsistenciaPersonalController@agregarMarcaFaltante',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\AsistenciaPersonalController@agregarMarcaFaltante',
        'as' => 'asistencia-personal.agregar-marca-faltante',
        'namespace' => NULL,
        'prefix' => '/asistencia-personal',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asistencia-personal.guardar-marcas-multiples' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'asistencia-personal/guardar-marcas-multiples',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\AsistenciaPersonalController@guardarMarcasMultiples',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\AsistenciaPersonalController@guardarMarcasMultiples',
        'as' => 'asistencia-personal.guardar-marcas-multiples',
        'namespace' => NULL,
        'prefix' => '/asistencia-personal',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asistencia-personal.' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asistencia-personal/obtener-todas-las-personas-test',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'auth',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:173:"function() {
        return \\response()->json([
            \'success\' => true,
            \'test\' => \'OK\',
            \'message\' => \'La ruta test funciona\'
        ]);
    }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"0000000000000d200000000000000000";}}',
        'as' => 'asistencia-personal.',
        'namespace' => NULL,
        'prefix' => '/asistencia-personal',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asistencia-personal.generated::HQ4BSRixNiYGwgSM' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asistencia-personal/test-simple',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:68:"function() {
        return \\response()->json([\'ok\' => true]);
    }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"0000000000000d220000000000000000";}}',
        'as' => 'asistencia-personal.generated::HQ4BSRixNiYGwgSM',
        'namespace' => NULL,
        'prefix' => '/asistencia-personal',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asistencia-personal.obtener-horas-extras-agregadas-batch' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'asistencia-personal/obtener-horas-extras-agregadas-batch',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\AsistenciaPersonalController@obtenerHorasExtrasAgregadasBatch',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\AsistenciaPersonalController@obtenerHorasExtrasAgregadasBatch',
        'as' => 'asistencia-personal.obtener-horas-extras-agregadas-batch',
        'namespace' => NULL,
        'prefix' => '/asistencia-personal',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asistencia-personal.obtener-horas-extras-agregadas' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asistencia-personal/obtener-horas-extras-agregadas/{codigo_persona}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\AsistenciaPersonalController@obtenerHorasExtrasAgregadas',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\AsistenciaPersonalController@obtenerHorasExtrasAgregadas',
        'as' => 'asistencia-personal.obtener-horas-extras-agregadas',
        'namespace' => NULL,
        'prefix' => '/asistencia-personal',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.valor-hora-extra.obtener' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/valor-hora-extra/{codigoPersona}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\ValorHoraExtraController@obtener',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\ValorHoraExtraController@obtener',
        'as' => 'api.valor-hora-extra.obtener',
        'namespace' => NULL,
        'prefix' => '/api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.valor-hora-extra.guardar' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/valor-hora-extra/guardar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'verified',
        ),
        'uses' => 'App\\Http\\Controllers\\Api_temp\\ValorHoraExtraController@guardar',
        'controller' => 'App\\Http\\Controllers\\Api_temp\\ValorHoraExtraController@guardar',
        'as' => 'api.valor-hora-extra.guardar',
        'namespace' => NULL,
        'prefix' => '/api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.pedidos-editable.' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/pedidos-editable/crear',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:105:"function() {
        return \\redirect()->route(\'asesores.pedidos-editable.crear-desde-cotizacion\');
    }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"0000000000000d290000000000000000";}}',
        'as' => 'asesores.pedidos-editable.',
        'namespace' => NULL,
        'prefix' => '/asesores/pedidos-editable',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.pedidos-editable.crear-desde-cotizacion' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/pedidos-editable/crear-desde-cotizacion',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\CrearPedidoEditableController@crearDesdeCotizacion',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\CrearPedidoEditableController@crearDesdeCotizacion',
        'as' => 'asesores.pedidos-editable.crear-desde-cotizacion',
        'namespace' => NULL,
        'prefix' => '/asesores/pedidos-editable',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.pedidos-editable.crear-nuevo' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/pedidos-editable/crear-nuevo',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\CrearPedidoEditableController@crearNuevo',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\CrearPedidoEditableController@crearNuevo',
        'as' => 'asesores.pedidos-editable.crear-nuevo',
        'namespace' => NULL,
        'prefix' => '/asesores/pedidos-editable',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.pedidos-editable.agregar-item' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'asesores/pedidos-editable/items/agregar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\CrearPedidoEditableController@agregarItem',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\CrearPedidoEditableController@agregarItem',
        'as' => 'asesores.pedidos-editable.agregar-item',
        'namespace' => NULL,
        'prefix' => '/asesores/pedidos-editable',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.pedidos-editable.eliminar-item' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'asesores/pedidos-editable/items/eliminar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\CrearPedidoEditableController@eliminarItem',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\CrearPedidoEditableController@eliminarItem',
        'as' => 'asesores.pedidos-editable.eliminar-item',
        'namespace' => NULL,
        'prefix' => '/asesores/pedidos-editable',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.pedidos-editable.obtener-items' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'asesores/pedidos-editable/items',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\CrearPedidoEditableController@obtenerItems',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\CrearPedidoEditableController@obtenerItems',
        'as' => 'asesores.pedidos-editable.obtener-items',
        'namespace' => NULL,
        'prefix' => '/asesores/pedidos-editable',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.pedidos-editable.validar' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'asesores/pedidos-editable/validar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\CrearPedidoEditableController@validarPedido',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\CrearPedidoEditableController@validarPedido',
        'as' => 'asesores.pedidos-editable.validar',
        'namespace' => NULL,
        'prefix' => '/asesores/pedidos-editable',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'asesores.pedidos-editable.crear' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'asesores/pedidos-editable/crear',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:asesor,admin,supervisor_pedidos',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\CrearPedidoEditableController@crearPedido',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Asesores\\CrearPedidoEditableController@crearPedido',
        'as' => 'asesores.pedidos-editable.crear',
        'namespace' => NULL,
        'prefix' => '/asesores/pedidos-editable',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cartera.pedidos' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'cartera/pedidos',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:cartera,admin',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:87:"function () {
        return \\view(\'cartera-pedidos.cartera-pedidos-supervisor\');
    }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"0000000000000d320000000000000000";}}',
        'as' => 'cartera.pedidos',
        'namespace' => NULL,
        'prefix' => '/cartera',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.cartera.opciones-filtro' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/cartera/opciones-filtro',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:cartera,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\CarteraPedidosController@obtenerOpcionesFiltro',
        'controller' => 'App\\Http\\Controllers\\CarteraPedidosController@obtenerOpcionesFiltro',
        'as' => 'api.cartera.opciones-filtro',
        'namespace' => NULL,
        'prefix' => '/api/cartera',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.cartera.aprobar' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/cartera/pedidos/{id}/aprobar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:cartera,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\CarteraPedidosController@aprobarPedido',
        'controller' => 'App\\Http\\Controllers\\CarteraPedidosController@aprobarPedido',
        'as' => 'api.cartera.aprobar',
        'namespace' => NULL,
        'prefix' => '/api/cartera',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.cartera.rechazar' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/cartera/pedidos/{id}/rechazar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:cartera,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\CarteraPedidosController@rechazarPedido',
        'controller' => 'App\\Http\\Controllers\\CarteraPedidosController@rechazarPedido',
        'as' => 'api.cartera.rechazar',
        'namespace' => NULL,
        'prefix' => '/api/cartera',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'api.cartera.factura-datos' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/cartera/pedidos/{id}/factura-datos',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:cartera,admin',
        ),
        'uses' => 'App\\Http\\Controllers\\CarteraPedidosController@obtenerDatosFactura',
        'controller' => 'App\\Http\\Controllers\\CarteraPedidosController@obtenerDatosFactura',
        'as' => 'api.cartera.factura-datos',
        'namespace' => NULL,
        'prefix' => '/api/cartera',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'register' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'register',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'guest',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\RegisteredUserController@create',
        'controller' => 'App\\Http\\Controllers\\Auth\\RegisteredUserController@create',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'register',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::8fKjH9LXDwbZlTxO' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'register',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'guest',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\RegisteredUserController@store',
        'controller' => 'App\\Http\\Controllers\\Auth\\RegisteredUserController@store',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'generated::8fKjH9LXDwbZlTxO',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'login' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'login',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'guest',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\AuthenticatedSessionController@create',
        'controller' => 'App\\Http\\Controllers\\Auth\\AuthenticatedSessionController@create',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'login',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::F8iaegPMBaURfYyK' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'login',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'guest',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\AuthenticatedSessionController@store',
        'controller' => 'App\\Http\\Controllers\\Auth\\AuthenticatedSessionController@store',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'generated::F8iaegPMBaURfYyK',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'auth.google' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'auth/google',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'guest',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\GoogleAuthController@redirect',
        'controller' => 'App\\Http\\Controllers\\Auth\\GoogleAuthController@redirect',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'auth.google',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'auth.google.callback' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'auth/google/callback',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'guest',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\GoogleAuthController@callback',
        'controller' => 'App\\Http\\Controllers\\Auth\\GoogleAuthController@callback',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'auth.google.callback',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'password.request' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'forgot-password',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'guest',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\PasswordResetLinkController@create',
        'controller' => 'App\\Http\\Controllers\\Auth\\PasswordResetLinkController@create',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'password.request',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'password.email' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'forgot-password',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'guest',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\PasswordResetLinkController@store',
        'controller' => 'App\\Http\\Controllers\\Auth\\PasswordResetLinkController@store',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'password.email',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'password.reset' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'reset-password/{token}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'guest',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\NewPasswordController@create',
        'controller' => 'App\\Http\\Controllers\\Auth\\NewPasswordController@create',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'password.reset',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'password.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'reset-password',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'guest',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\NewPasswordController@store',
        'controller' => 'App\\Http\\Controllers\\Auth\\NewPasswordController@store',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'password.store',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'verification.notice' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'verify-email',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\EmailVerificationPromptController@__invoke',
        'controller' => 'App\\Http\\Controllers\\Auth\\EmailVerificationPromptController',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'verification.notice',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'verification.verify' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'verify-email/{id}/{hash}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'signed',
          3 => 'throttle:6,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\VerifyEmailController@__invoke',
        'controller' => 'App\\Http\\Controllers\\Auth\\VerifyEmailController',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'verification.verify',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'verification.send' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'email/verification-notification',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'throttle:6,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\EmailVerificationNotificationController@store',
        'controller' => 'App\\Http\\Controllers\\Auth\\EmailVerificationNotificationController@store',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'verification.send',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'password.confirm' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'confirm-password',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\ConfirmablePasswordController@show',
        'controller' => 'App\\Http\\Controllers\\Auth\\ConfirmablePasswordController@show',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'password.confirm',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::Ne2NyYSjUEXizfSO' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'confirm-password',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\ConfirmablePasswordController@store',
        'controller' => 'App\\Http\\Controllers\\Auth\\ConfirmablePasswordController@store',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'generated::Ne2NyYSjUEXizfSO',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'password.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'password',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\PasswordController@update',
        'controller' => 'App\\Http\\Controllers\\Auth\\PasswordController@update',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'password.update',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'logout' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'logout',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\AuthenticatedSessionController@destroy',
        'controller' => 'App\\Http\\Controllers\\Auth\\AuthenticatedSessionController@destroy',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'logout',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'logout.get' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'logout',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:261:"function () {
        \\Illuminate\\Support\\Facades\\Auth::guard(\'web\')->logout();
        \\request()->session()->invalidate();
        \\request()->session()->regenerateToken();
        return \\redirect(\'/\')->with(\'success\', \'Sesión cerrada correctamente\');
    }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"0000000000000d4a0000000000000000";}}',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'logout.get',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'despacho.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'despacho',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'check.despacho.role',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Despacho\\DespachoController@index',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Despacho\\DespachoController@index',
        'namespace' => NULL,
        'prefix' => '/despacho',
        'where' => 
        array (
        ),
        'as' => 'despacho.index',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'despacho.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'despacho/{pedido}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'check.despacho.role',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Despacho\\DespachoController@show',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Despacho\\DespachoController@show',
        'namespace' => NULL,
        'prefix' => '/despacho',
        'where' => 
        array (
        ),
        'as' => 'despacho.show',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'pedido' => '[0-9]+',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'despacho.guardar' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'despacho/{pedido}/guardar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'check.despacho.role',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Despacho\\DespachoController@guardarDespacho',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Despacho\\DespachoController@guardarDespacho',
        'namespace' => NULL,
        'prefix' => '/despacho',
        'where' => 
        array (
        ),
        'as' => 'despacho.guardar',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'pedido' => '[0-9]+',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'despacho.print' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'despacho/{pedido}/print',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'check.despacho.role',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Despacho\\DespachoController@printDespacho',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Despacho\\DespachoController@printDespacho',
        'namespace' => NULL,
        'prefix' => '/despacho',
        'where' => 
        array (
        ),
        'as' => 'despacho.print',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'pedido' => '[0-9]+',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'despacho.obtener' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'despacho/{pedido}/obtener-despachos',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'check.despacho.role',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Despacho\\DespachoController@obtenerDespachos',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Despacho\\DespachoController@obtenerDespachos',
        'namespace' => NULL,
        'prefix' => '/despacho',
        'where' => 
        array (
        ),
        'as' => 'despacho.obtener',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'pedido' => '[0-9]+',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'despacho.factura-datos' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'despacho/{pedido}/factura-datos',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'check.despacho.role',
        ),
        'uses' => 'App\\Infrastructure\\Http\\Controllers\\Despacho\\DespachoController@obtenerFacturaDatos',
        'controller' => 'App\\Infrastructure\\Http\\Controllers\\Despacho\\DespachoController@obtenerFacturaDatos',
        'namespace' => NULL,
        'prefix' => '/despacho',
        'where' => 
        array (
        ),
        'as' => 'despacho.factura-datos',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'pedido' => '[0-9]+',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::SC02IF8yEwjqJ27h' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'pedidos-test',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:653:"function() {
    $user = \\auth()->user();
    return \\response()->json([
        \'mensaje\' => \'Ruta de bodega funciona\',
        \'autenticado\' => $user ? true: false,
        \'usuario_actual\' => $user ? $user->name : \'No autenticado\',
        \'usuario_id\' => $user ? $user->id : null,
        \'roles\' => $user ? $user->getRoleNames()->toArray() : [],
        \'role_ids\' => $user ? $user->roles_ids : null,
        \'role_id\' => $user ? $user->role_id : null,
        \'permisos\' => $user ? $user->getAllPermissions()->pluck(\'name\')->toArray() : [],
        \'tiene_permiso_bodega\' => $user ? $user->hasPermissionTo(\'view-bodega-pedidos\') : false,
    ]);
}";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"0000000000000d0c0000000000000000";}}',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'generated::SC02IF8yEwjqJ27h',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::fzwyhSq62myL0qxK' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'pedidos-temp',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\Bodega\\PedidosController@index',
        'controller' => 'App\\Http\\Controllers\\Bodega\\PedidosController@index',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'generated::fzwyhSq62myL0qxK',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'gestion-bodega.' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'gestion-bodega',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:bodeguero',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:80:"function () {
        return \\redirect()->route(\'gestion-bodega.pedidos\');
    }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"0000000000000d550000000000000000";}}',
        'as' => 'gestion-bodega.',
        'namespace' => NULL,
        'prefix' => '/gestion-bodega',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'gestion-bodega.pedidos' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'gestion-bodega/pedidos',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:bodeguero',
        ),
        'uses' => 'App\\Http\\Controllers\\Bodega\\PedidosController@index',
        'controller' => 'App\\Http\\Controllers\\Bodega\\PedidosController@index',
        'as' => 'gestion-bodega.pedidos',
        'namespace' => NULL,
        'prefix' => '/gestion-bodega',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'gestion-bodega.dashboard' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'gestion-bodega/dashboard',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:bodeguero',
        ),
        'uses' => 'App\\Http\\Controllers\\Bodega\\PedidosController@dashboard',
        'controller' => 'App\\Http\\Controllers\\Bodega\\PedidosController@dashboard',
        'as' => 'gestion-bodega.dashboard',
        'namespace' => NULL,
        'prefix' => '/gestion-bodega',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'gestion-bodega.entregar' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'gestion-bodega/pedidos/{id}/entregar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:bodeguero',
        ),
        'uses' => 'App\\Http\\Controllers\\Bodega\\PedidosController@entregar',
        'controller' => 'App\\Http\\Controllers\\Bodega\\PedidosController@entregar',
        'as' => 'gestion-bodega.entregar',
        'namespace' => NULL,
        'prefix' => '/gestion-bodega',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'gestion-bodega.actualizar-observaciones' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'gestion-bodega/pedidos/observaciones',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:bodeguero',
        ),
        'uses' => 'App\\Http\\Controllers\\Bodega\\PedidosController@actualizarObservaciones',
        'controller' => 'App\\Http\\Controllers\\Bodega\\PedidosController@actualizarObservaciones',
        'as' => 'gestion-bodega.actualizar-observaciones',
        'namespace' => NULL,
        'prefix' => '/gestion-bodega',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'gestion-bodega.actualizar-fecha' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'gestion-bodega/pedidos/fecha',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:bodeguero',
        ),
        'uses' => 'App\\Http\\Controllers\\Bodega\\PedidosController@actualizarFecha',
        'controller' => 'App\\Http\\Controllers\\Bodega\\PedidosController@actualizarFecha',
        'as' => 'gestion-bodega.actualizar-fecha',
        'namespace' => NULL,
        'prefix' => '/gestion-bodega',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'gestion-bodega.export' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'gestion-bodega/pedidos/export',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'role:bodeguero',
        ),
        'uses' => 'App\\Http\\Controllers\\Bodega\\PedidosController@export',
        'controller' => 'App\\Http\\Controllers\\Bodega\\PedidosController@export',
        'as' => 'gestion-bodega.export',
        'namespace' => NULL,
        'prefix' => '/gestion-bodega',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'procesos.activar-recibo-simple' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'procesos/{procesoId}/activar-recibo',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:1534:"function(\\Illuminate\\Http\\Request $request, $procesoId) {
    try {
        // Cambiar estado directamente en la tabla
        $proceso = \\DB::table(\'pedidos_procesos_prenda_detalles\')
            ->where(\'id\', $procesoId)
            ->first();
            
        if (!$proceso) {
            return \\response()->json([
                \'success\' => false,
                \'message\' => \'Proceso no encontrado\'
            ], 404);
        }
        
        $activar = $request->input(\'activar\');
        
        if ($activar) {
            \\DB::table(\'pedidos_procesos_prenda_detalles\')
                ->where(\'id\', $procesoId)
                ->update([
                    \'estado\' => \'APROBADO\',
                    \'fecha_aprobacion\' => \\now(),
                    \'aprobado_por\' => \\auth()->id()
                ]);
        } else {
            \\DB::table(\'pedidos_procesos_prenda_detalles\')
                ->where(\'id\', $procesoId)
                ->update([
                    \'estado\' => \'PENDIENTE\',
                    \'fecha_aprobacion\' => null,
                    \'aprobado_por\' => null
                ]);
        }
        
        return \\response()->json([
            \'success\' => true,
            \'message\' => $activar ? \'Recibo activado correctamente\' : \'Recibo desactivado correctamente\'
        ]);
        
    } catch (\\Exception $e) {
        return \\response()->json([
            \'success\' => false,
            \'message\' => \'Error al actualizar estado: \' . $e->getMessage()
        ], 500);
    }
}";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"0000000000000d540000000000000000";}}',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'procesos.activar-recibo-simple',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::vo7HxcGuo97AUlJl' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'POST',
        2 => 'HEAD',
      ),
      'uri' => 'broadcasting/auth',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => '\\Illuminate\\Broadcasting\\BroadcastController@authenticate',
        'controller' => '\\Illuminate\\Broadcasting\\BroadcastController@authenticate',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'excluded_middleware' => 
        array (
          0 => 'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
        ),
        'as' => 'generated::vo7HxcGuo97AUlJl',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'storage.local' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'storage/{path}',
      'action' => 
      array (
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:3:{s:4:"disk";s:5:"local";s:6:"config";a:5:{s:6:"driver";s:5:"local";s:4:"root";s:62:"C:\\Users\\Usuario\\Documents\\mundoindustrial\\storage\\app/private";s:5:"serve";b:1;s:5:"throw";b:0;s:6:"report";b:0;}s:12:"isProduction";b:0;}s:8:"function";s:323:"function (\\Illuminate\\Http\\Request $request, string $path) use ($disk, $config, $isProduction) {
                    return (new \\Illuminate\\Filesystem\\ServeFile(
                        $disk,
                        $config,
                        $isProduction
                    ))($request, $path);
                }";s:5:"scope";s:47:"Illuminate\\Filesystem\\FilesystemServiceProvider";s:4:"this";N;s:4:"self";s:32:"0000000000000d760000000000000000";}}',
        'as' => 'storage.local',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'path' => '.*',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
  ),
)
);
