// ===============================================================
//   КОНСТРУКТОР ОБРАБОТЧИК АКТИВНОСТИ ВКЛАДКИ БРАУЗЕРА
// ===============================================================

function WindowActiveTabHelper(){


    this.EVENT_BLUR_TYPE = 'window_blur';
    this.EVENT_FOCUS_TYPE = 'window_focus';
    this.EVENT_LAST_BLUR_TAB_TYPE = 'window_active';

    // присваиваем текущему окну id
    this.window_tab_id = `${Date.now()}`;
    this.window_tab_is_blur = false;
    this._window_tab_is_last_blur = false;
    this._storage_key_focus = 'lastFocusTabId';
    this._storage_key_blur = 'lastBlurTabId';

    this._storage_open_window_key = 'allWindowId';
    this._storage_close_tab_key = 'closeTab';
    

    // записываем в локал стораж id текущего окна
    localStorage.setItem(this._storage_key_focus, this.window_tab_id);
    

    this.isLastActiveWindowTab = function(){
        return (localStorage.getItem(this._storage_key_focus) === this.window_tab_id);
    };




    this._setWindowDataToStorage = function (){
        let all_window_data_id = this.getAllOpenWindowData();
        all_window_data_id[this.window_tab_id] = true;

        let all_window_data_id_json = JSON.stringify(all_window_data_id);
        localStorage.setItem(this._storage_open_window_key, all_window_data_id_json);
    };

    this._deleteWindowDataFromStorage = function (){
        let all_window_data_id = this.getAllOpenWindowData();
        delete all_window_data_id[this.window_tab_id];

        let all_window_data_id_json = JSON.stringify(all_window_data_id);
        localStorage.setItem(this._storage_open_window_key, all_window_data_id_json);
    };
    
    this.getAllOpenWindowData = function(){
        let all_window_id = localStorage.getItem(this._storage_open_window_key);
        let all_window_id_json = false;
        try{
            all_window_id_json = JSON.parse(all_window_id);
        }catch(e){

        }
        return (!!all_window_id_json ? all_window_id_json : {} );
    };

    this._setWindowDataToStorage();




    // ----------------------------
    // add events
    // ----------------------------
        let self = this;





        // закрытие окна
        this._onUnloadHandler = function(){
            self._deleteWindowDataFromStorage();
            if(!self.window_tab_is_blur){
                localStorage.setItem(self._storage_close_tab_key, self.window_tab_id);
            }
            
        };
        window.addEventListener('beforeunload', this._onUnloadHandler, {once:true});  


        this._onUnloadStorageHandler = function(e){
            // console.log('ключ '+e.key);
            if( e.key !== self._storage_close_tab_key ) return;

            let all_window_data = self.getAllOpenWindowData();
            // console.log('all data ',all_window_data);
            // берем первый элемент
            for ( var key in all_window_data ) {
                break;
            }
            // console.log('key ',key);
            // console.log('id ',self.window_tab_id);
            if( key !== self.window_tab_id ) return;

            self._window_tab_is_last_blur = false;

            // при фокусе окна устанавлиаем id текущего окна в стораж
            localStorage.setItem(self._storage_key_focus, self.window_tab_id);

            // выполняем обработчики назначеные пользователем при фокусе
            self._initUserEvents(self.EVENT_FOCUS_TYPE);
        };

        window.addEventListener('storage', this._onUnloadStorageHandler);  













        // при блюре окна ставим метку для текущего окна - что окно свернуто
        this._onBlurHandler = function(){
            // если окно уже в блюре 
            //if( !!self.window_tab_is_blur ) return;

            //console.log('blur');
            self.window_tab_is_blur = true;
            
            // выполняем обработчики назначеные пользователем при блюре
            self._initUserEvents(self.EVENT_BLUR_TYPE);
            
            localStorage.setItem(self._storage_key_focus, self.window_tab_id);
        };
        window.addEventListener('blur', this._onBlurHandler);



        this._onFocusHandler = function(){
            //console.log('focus');
            //if( localStorage.getItem(self._storage_key_focus) === self.window_tab_id ) return;
            self.window_tab_is_blur = false;
            self._window_tab_is_last_blur = false;
            // при фокусе окна устанавлиаем id текущего окна в стораж
            localStorage.setItem(self._storage_key_focus, self.window_tab_id);

            // выполняем обработчики назначеные пользователем при фокусе
            self._initUserEvents(self.EVENT_FOCUS_TYPE);

        };
        window.addEventListener('focus', this._onFocusHandler);

        /*window.addEventListener('load', () => {
            document.body.click();
        });*/


        // событие "storage" сработает во всех окнах, кроме окна где это событие было вызвано 
        // если пришло событие значит где то в другом окне сработал фокус - выполняем действия для блюр
        this._onStorageHandler = function(e){

            // обрабатываем только обновления для текущего ключа при изменении в хранилище
            if( e.key !== self._storage_key_focus ) return;

            //if( e.newValue !== self.window_tab_id ) return;
            if( self._window_tab_is_last_blur ) return;
            // выполняем обработчики назначеные пользователем при блюре
            self._initUserEvents(self.EVENT_LAST_BLUR_TAB_TYPE);
            self._window_tab_is_last_blur = true;
        };
        
        window.addEventListener('storage', this._onStorageHandler);


    // ----------------------------
    // User events
    // ----------------------------
        this._eventsCallback = {};

        this._addUserHandler = function (event_type, callback, option){
            if( !this._eventsCallback[event_type] ) this._eventsCallback[event_type] = [];
            this._eventsCallback[event_type].push({callback,option});
            return this._eventsCallback[event_type].length - 1;
        };

        this.addHandlerOnFocus = function ( callback, option ){
            return this._addUserHandler(this.EVENT_FOCUS_TYPE, callback, option);   
        };

        this.addHandlerOnBlur = function ( callback, option ){
            return this._addUserHandler(this.EVENT_BLUR_TYPE, callback, option);            
        };

        this.addHandlerOnLastBlurTabChange = function ( callback, option ){
            return this._addUserHandler(this.EVENT_LAST_BLUR_TAB_TYPE, callback, option);           
        };



        this._initUserEvents = function( event_type, event_data=null, option=null ){
            if( !this._eventsCallback[event_type] ) return;

            if( event_type === this.EVENT_LAST_BLUR_TAB_TYPE && self._window_tab_is_last_blur ) return;

            let event_count = this._eventsCallback[event_type].length;
            
            for (var i = 0; i < event_count; i++) {
                if( !this._eventsCallback[event_type][i] || !this._eventsCallback[event_type][i]['callback'] ) continue;
                let callback_resp = this._eventsCallback[event_type][i]['callback']( event_data );
            }
        };


    // ----------------------------
    // remove user events
    // ----------------------------
        this.removeEvents = function( event_type, event_id=null ){

            if( !this._eventsCallback[event_type] ) return false;

            // если не передали event_id - очищаем все события для данного типа
            if( !event_id && event_id !== 0 ){
                this._eventsCallback[event_type] = [];
                return true;
            }

            // удаление конкретного обработчика
            if( !!this._eventsCallback[event_type][event_id] ){
                this._eventsCallback[event_type][event_id] = null;
            }

        };


    // ----------------------------
    // Destructor
    // ---------------------------- 
    
        this.destroy = function(){
            window.removeEventListener('focus', this._onFocusHandler);
            window.removeEventListener('blur', this._onBlurHandler);
            window.removeEventListener('storage', this._onStorageHandler);
            window.removeEventListener('storage', this._onUnloadStorageHandler);
        };


}

window.activeBrowserTabHelper = new WindowActiveTabHelper();