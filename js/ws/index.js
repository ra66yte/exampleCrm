let ws = new WebSocket("ws://127.0.0.1:8088/system/Server/start.php"),
    indicator = $('#ws'),
    userId = getCookie('user_id'),
    userHash = getCookie('hash'),
    userLocation = '',
    userActiveOrderStatus = $('#orders-status-list .tab-status-active').length ? $('.tab-status-active').attr('data-id') : 0;

ws.onopen = function() {
    let userConnect = {
            action: 'enter',
            data: {
                id: userId,
                hash: userHash,
                location: userLocation,
                activeStatuses: {
                    'orders': userActiveOrderStatus
                }
            }
        }

    ws.send(JSON.stringify(userConnect));

    indicator.find('s').addClass('websocket-ok').text('Ok');
};

ws.onclose = function(event) {
    if (event.wasClean) {
        indicator.find('s').addClass('websocket-error').text('Closed');
        console.log('The connection is closed.');
    } else {
        indicator.find('s').addClass('websocket-error').text('Error');
        console.log('Connection failure');
    }
};

ws.onmessage = function(event) {
    let jsonData = JSON.parse(event.data),
        action = jsonData.hasOwnProperty('action') ? jsonData.action : null;

    // console.log(jsonData)

    if (action) {
        let data = jsonData.data,
            places = {
                'orders': 'orders__table',
                'order_statuses': 'order-statuses__table'
            },
            methods = {
                'orders': typeof (updateStatusesOrderCount) === 'function' ? updateStatusesOrderCount : null
            }
            table = data.hasOwnProperty('location') ? $('#' + places[data.location] + ' tbody') : null,
            countRowsStart = 0,
            countRows = Number($('#pagination-total').text()),
            countRowsOnPage = Number($('#pagination-now').text());

        switch (action) {
            case 'Ping':
                ws.send('{"action":"Pong"}');
                break;
            case 'update counts':
                if (Object.keys(methods).includes(data.location) && data.location != '') {
                    methods[data.location]();
                }
                break;
            case 'add item':
                let item = data.rowData,
                    newRow;

                if (data.location == 'orders') {
                    newRow = ('<tr data-id="' + item.id_item + '" class="table__item' + (item.blocked == 1 ? ' blocked-row' : '') + '" style="background-color: ' + item.status_color + '">' +
                                '<td><input type="checkbox" name="item[' + item.id_item + ']">' + item.id_item + '</td>' +
                                '<td style="color: #757575" align="center"><small>' + (item.updated == '' ? '<div class="blink">новый</div>' : item.id_order) + '</small></td>' +
                                '<td>' + item.customer + '</td>' +
                                '<td align="center">' + item.country + '</td>' +
                                '<td align="center">' + item.phone + '</td>' +
                                '<td>' + item.comment + '</td>' +
                                '<td align="center"><b>' + item.amount + '</b></td>' +
                                '<td>' + item.products + '</td>' +
                                '<td class="center table__item-icon">' + item.payment_method + '</td>' +
                                '<td class="center table__item-icon">' + item.delivery_method + '</td>' +
                                '<td>' + item.delivery_adress + '</td>' +
                                '<td>' + item.ttn + '</td>' +
                                '<td>' + item.ttn_status + '</td>' +
                                '<td>' + item.departure_date + '</td>' +
                                '<td>' + item.date_added + '</td>' +
                                '<td>' + (item.updated === true ? '' : item.updated) + '</td>' +
                                '<td>' + item.employee + '</td>' +
                                '<td>' + item.site + '</td>' +
                                '<td>' + item.ip + '</td>' +
                                '<td>' + item.order_status + '</td>' +
                                '<td>' + item.complete + '</td>' +
                            '</tr>');
                }

                if (Object.keys(places).includes(data.location) && data.location != '') {
                    if (table.length) {
                        table.prepend(newRow);
                        if (Object.keys(methods).includes(data.location)) {
                            methods[data.location]();
                            
                            if (table.find('tr.no-result').length) {
                                table.find('tr.no-result').remove();
                            }

                            $('#pagination-now').text(countRowsOnPage + 1);
                            $('#pagination-total').text(countRows + 1);

                            if (table.find('tr').length == 1) {
                                countRowsStart = Number($('#pagination-total').text()) - Number($('#pagination-now').text());
                                $('#pagination-start').text(countRowsStart == 0 ? 1 : countRowsStart);
                            } 

                        }
                    }
                }
                break;
            case 'remove item':
                if (Object.keys(places).includes(data.location) && data.location != '') {
                    if (table.length) {
                        let onPage = false,
                            countTd = 0;

                        if (table.find('tr[data-id="' + data.itemId + '"]').length) {
                            countTd = table.find('tr[data-id="' + data.itemId + '"] td').length;
                            table.find('tr[data-id="' + data.itemId + '"]').remove();
                            onPage = true;
                        }
                        if (Object.keys(methods).includes(data.location)) {
                            methods[data.location]();
                            
                            if (onPage) {
                                $('#pagination-now').text(countRowsOnPage - 1);
                                $('#pagination-total').text(countRows - 1);
                                if (!table.find('tr').length) {
                                    if (data.location == 'orders' /* || */) {
                                        location.reload();
                                    } else {
                                        let emptyTable = '<tr class="no-result">' +
                                                            '<td colspan="' + countTd  + '">Здесь ничего нет.</td>' +
                                                         '</tr>';
                                        table.html(emptyTable);
                                    }
                                    countRowsStart = Number($('#pagination-total').text()) - Number($('#pagination-now').text());
                                    $('pagination-start').text(countRowsStart);
                                }
                                
                            }
                            
                        }
                    }
                }
                break;
            case 'lock item':
                if (Object.keys(places).includes(data.location) && data.location != '') {
                    if (table.length) {
                        if (table.find('tr[data-id="' + data.itemId + '"]').length) table.find('tr[data-id="' + data.itemId + '"]').addClass('blocked-row');
                        if (table.find('tr[data-id="' + data.itemId + '"]').hasClass('table__active')) table.find('tr[data-id="' + data.itemId + '"]').toggleClass('table__active');
                        if (!table.find('tr.table__active').length) {
                            $('.status-panel__count').text('').hide();
                        }
                    }
                }
                break;
            case 'unlock item':
                if (Object.keys(places).includes(data.location) && data.location != '') {
                    if (table.length) {
                        if (table.find('tr[data-id="' + data.itemId + '"]').length) table.find('tr[data-id="' + data.itemId + '"]').removeClass('blocked-row');
                    }
                }
                break;
        }
    }
};

ws.onerror = function(error) {
    indicator.find('s').addClass('websocket-error').text('Error');
    // Error
};

// waitForConnection
const waitForOpenConnection = (ws) => {
    return new Promise((resolve, reject) => {
        let currentAttempt = 0;
        const maxNumberOfAttempts = 10,
              intervalTime = 200; // ms

        const interval = setInterval(() => {
            if (currentAttempt > maxNumberOfAttempts - 1) {
                clearInterval(interval)
                reject(new Error('Maximum number of attempts exceeded'));
            } else if (ws.readyState === ws.OPEN) {
                clearInterval(interval);
                resolve();
            }
            currentAttempt++;
        }, intervalTime)
    });
}

const sendMessage = async (ws, msg) => {
    if (ws.readyState !== ws.OPEN) {
        try {
            await waitForOpenConnection(ws);
            ws.send(msg);
        } catch (err) { console.error(err); }
    } else {
        ws.send(msg);
    }
}