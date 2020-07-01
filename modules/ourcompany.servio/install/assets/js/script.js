window.onload = function()
{
    let serv = new ServioPopup();
}


class ServioPopup
{
    constructor()
    {

        this.url = {
            ajax: '/local/modules/ourcompany.servio/ajax.php',
        }

        this.filters = {
            dateFrom: '',
            dateTo: '',
            roomCategory: '',
            adults: 1,
            childs: 0,
            childsAges: '',
            companyId: 0,
        }
        // this.minDates = {
        //     today: '',
        //     nextDay: ''
        // }
        // this.list = {
        //     roomCategory: [],
        //     errors: [],
        //     categoryPriceData: {},
        // }
        // this.reserve = {
        //     reserveId: 0,
        //     reserveData: {},
        // }
        // this.company = {
        //     // id: 0,
        //     // title: '',
        // }
        this.deal = {
            id : 0,
            reserveId: 0,
        }
        this.categoriesObj = {} //для хранения данных категорий для дополнения селектовпри выборе


        /*
        * По шагам
        * */

        // 0.1 Получение Id сделки (для получения отве., контакта, записи в поля)
        // this.getDealIdAndReserveId()

        let matchMassive, result = false, self = this
        if(matchMassive = window.location.href.match(/\/crm\/deal\/details\/([\d]+)/i))
        {
            this.deal.id = Number(matchMassive[1])

            if(this.deal.id > 0)
            {
                this.makeAjaxRequest(this.url.ajax,
                    {
                        'ACTION' : 'GET_DEAL_RESERVE_ID',
                        'DEAL_ID' : this.deal.id
                    },
                    function (response) {
                        self.deal.reserveId = response;
                        // console.log('DEAL RESERVE ID',self.deal);

                        if(self.deal.id > 0 && self.deal.reserveId === 0)
                        {
                            //здесь popup с формой

                            // self.loadServioPopup();

                            //new popup #1
                            // self.loadServioReserveFormPopup()
                            // self.loadServioReserveFormPopupV3()
                            self.loadReservePopupV4()
                            // console.log('DEAL ID > 0');
                        }
                        else if(self.deal.id > 0 && self.deal.reserveId > 0)
                        {
                            //здесь popup с полученным по id данными резерва

                            console.log('NEW POPUP WITH RESERVE DATA');

                            self.loadServioReservePopup()
                        }
                        else
                        {
                            //иначе форма без возможности резерва
                            // self.loadServioPopup();
                        }

                    })
            }
        }


    }

    //отображение формы
    // loadServioPopup()
    // {
    //     // 1. Заполнение полей "с" и "по" текущими датой и + 1 день
    //     this.fillDatesOnStart()
    //
    //     // 2. Запрос данных компании
    //     this.getCompanyInfo()
    //
    //     // // 3. Запрос данных по фильтрам
    //     // this.getRoomsByFilter()
    //
    //     //test request
    //     // this.makeAjaxRequest(this.url.ajax,{'test data':'lol'},function (response) {
    //     //     console.log('callback function from ajax',response)
    //     // })
    //
    //     let servioBtn = document.getElementById('servio') //.addEventListener('click',this.makePopup({'yyy':'iii'}))
    //
    //     if(servioBtn !==  null)
    //     {
    //         let self = this
    //         servioBtn.onclick = () => {
    //
    //             // 3. Запрос данных по фильтрам
    //             this.getRoomsByFilter()
    //
    //
    //
    //             let htmlContent = this.returnFormHtmlInPopup()
    //
    //
    //             let popupBtnevents =
    //                 {
    //                     onPopupClose: function(PopupWindow) {
    //                         // Событие при закрытии окна
    //                         PopupWindow.destroy()
    //                     },
    //
    //                     // События при показе окна
    //                     onPopupShow: function() {
    //
    //
    //                         // 8. Создание резерва.
    //                         $('#add_servio_reserve').click(function () {
    //                             self.addReservation()
    //                         });
    //                     },
    //                 }
    //             this.makePopup('servio-hotel-reservation',htmlContent,'Hotel Reservation',popupBtnevents)
    //
    //
    //             //4.Изменение поля Date_From
    //             let dateFrom = document.getElementById('dateFrom')
    //             if(dateFrom !== null)
    //             {
    //                 dateFrom.onchange =  function () {
    //                     self.changeDateStart();
    //                     self.showReserveButton();
    //                 }
    //             }
    //
    //             //5. Изменение поля Date_To
    //             let dateTo = document.getElementById('dateTo')
    //             if(dateTo !== null)
    //             {
    //                 dateTo.onchange = function () {
    //                     self.changeFinishDate()
    //                     self.showReserveButton();
    //                 }
    //             }
    //
    //             //4. Изменение Селекта
    //             let roomsCategorySelect = document.getElementById('roomCategory')
    //             if(roomsCategorySelect !== null)
    //             {
    //                 roomsCategorySelect.onchange =  function () {
    //                     //ОБНОВЛЕНИЕ ПОЛЕЙ ОБЪЕКТА
    //                     self.takeFormData();
    //                     self.showReserveButton();
    //                 }
    //             }
    //
    //             // 6,7 Изменение значений в полях Adults и Childs
    //             let adultsAndChildFields = document.querySelectorAll('#adults, #childs')
    //             if(adultsAndChildFields.length > 0)
    //             {
    //                 // let roomCatField = document.getElementById('roomCategory')
    //                 for(let elem of adultsAndChildFields)
    //                 {
    //                     //запрос категорий при изменении значений полей
    //                     elem.onchange = function () {
    //                         self.changeTextFields()
    //                         self.showReserveButton();
    //                     }
    //
    //                     //удаление из полей всего кроме цифр
    //                     elem.onkeyup = function () {
    //                         self.clearAllExeptnums(this)
    //                     }
    //                 }
    //             }
    //
    //         }
    //     }
    // }



    /*
    * Get deal id
    * */
    // getDealIdAndReserveId()
    // {
    //     let matchMassive, result = false, self = this
    //     if(matchMassive = window.location.href.match(/\/crm\/deal\/details\/([\d]+)/i))
    //     {
    //         this.deal.id = Number(matchMassive[1])
    //
    //         if(this.deal.id > 0)
    //         {
    //             this.makeAjaxRequest(this.url.ajax,
    //                 {
    //                     'ACTION' : 'GET_DEAL_RESERVE_ID',
    //                     'DEAL_ID' : this.deal.id
    //                 },
    //                 function (response) {
    //                     self.deal.reserveId = response;
    //                     // console.log('DEAL RESERVE ID',self.deal);
    //
    //                     if(self.deal.id > 0 && self.deal.reserveId === 0)
    //                     {
    //                         //здесь popup с формой
    //
    //                         // self.loadServioPopup();
    //
    //                         //new popup #1
    //                         self.loadServioReserveFormPopup()
    //
    //                         // console.log('DEAL ID > 0');
    //                     }
    //                     else if(self.deal.id > 0 && self.deal.reserveId > 0)
    //                     {
    //                         //здесь popup с полученным по id данными резерва
    //
    //                         console.log('NEW POPUP WITH RESERVE DATA');
    //
    //                         self.loadServioReservePopup()
    //                     }
    //                     else
    //                     {
    //                         //иначе форма без возможности резерва
    //                         // self.loadServioPopup();
    //                     }
    //
    //                 })
    //         }
    //     }
    //
    //     // console.log('DEAL URI',dealUri)
    // }

    /*
    * Fill dates on page start
    * */
    // fillDatesOnStart()
    // {
    //     let self = this,
    //         dateStart = new Date(),
    //         dateFinish = new Date();
    //
    //     dateFinish.setDate(dateFinish.getDate()  + 1);
    //
    //     // console.log(this.createDate(new Date('2020-06-04')));
    //
    //     this.minDates.today = this.createDate(dateStart)
    //     this.minDates.nextDay = this.createDate(dateFinish)
    //
    //     this.filters.dateFrom = this.createDate(dateStart);
    //     this.filters.dateTo = this.createDate(dateFinish);
    // }


    /*
     * Universal ajax request
      *  */
    makeAjaxRequest(urlS,dataS,closeFunction = false)
    {
        if(!closeFunction) closeFunction = function () {}

        let self = this
        BX.ajax
        ({
            method: "POST",
            url: urlS,
            data: dataS,
            dataType: "json",
            onsuccess: function (response) {
                // console.log('Custom Ajax request',response)
                closeFunction(response)
            }
        })
    }

    // returnFormHtmlInPopup()
    // {
    //     let errors = '';
    //
    //     // if(this.list.errors.length > 0)
    //     // {
    //     //     for(let err of this.list.errors)
    //     //     {
    //     //         // console.log('ERRRRR',err)
    //     //
    //     //         errors += `
    //     //             <div class="ui-alert ui-alert-danger">
    //     //                 <span class="ui-alert-message"><strong>Error!</strong> ${err}</span>
    //     //             </div>
    //     //             `
    //     //     }
    //     // }
    //
    //     return `
    //         <form id="servio_popup" onsubmit="return false" autocomplete="off">
    //
    //             <div class="form-row">
    //                 <div class="col-sm-6 form-group">
    //                     <label for="dateFrom" class="col-form-label-sm">Date From</label>
    //                     <input type="date" name="dateFrom" class="form-control form-control-sm tm-popup-task-form-textbox bx-focus" id="dateFrom" title="Select date from" autocomplete="off"
    //                         value="${this.filters.dateFrom}"
    //                         min="${this.minDates.today}"
    //                         >
    //                 </div>
    //                 <div class="col-sm-6 form-group">
    //                     <label for="dateTo" class="col-form-label-sm">Date To</label>
    //                     <input type="date" name="dateTo" class="form-control form-control-sm tm-popup-task-form-textbox bx-focus" id="dateTo" title="Select date from" autocomplete="off"
    //                         value="${this.filters.dateTo}"
    //                          min="${this.minDates.nextDay}"
    //                         >
    //                 </div>
    //             </div>
    //
    //             <div class="form-row">
    //                 <div class="form-group col-sm">
    //                     <label for="adults">Adults</label>
    //                     <input id="adults" name="adults" class="form-control form-control-sm tm-popup-task-form-textbox bx-focus"
    //                            value="${this.filters.adults}"
    //                            >
    //                 </div>
    //             </div>
    //
    //             <div class="form-row">
    //                 <div class="form-group col-sm">
    //                     <label for="childs">Childs</label>
    //                     <input id="childs" name="childs" class="form-control form-control-sm tm-popup-task-form-textbox bx-focus"
    //                             value="${this.filters.childs}"
    //                            >
    //                 </div>
    //             </div>
    //
    //             <div class="form-row">
    //                <div class="form-group col-sm">
    //                    <label for="roomCategory">Room Category</label>
    //                    <select id="roomCategory" name="roomCategory" class="form-control form-control-sm tm-popup-task-form-textbox bx-focus">
    //                    </select>
    //                </div>
    //             </div>
    //
    //             <div id="servio_price_info" class="text-center">
    //
    //             </div>
    //
    //              <button type="button" id="add_servio_reserve" class="mt-2 ui-btn ui-btn-danger-dark ui-btn-icon-task ui-btn-round">
    //                 Reserve!
    //              </button>
    //
    //
    //               <div id="test_table"></div>
    //         </form>`
    // }

    // makePopup(popupTechName,htmlContent,popupTitle,myBtnevents)
    // {
    //
    //     let PopupProductProvider = BX.PopupWindowManager.create(popupTechName, BX('element'), {
    //         content: htmlContent,
    //         width: 500, // ширина окна
    //         // height: 800, // высота окна
    //         zIndex: 100, // z-index
    //         closeIcon: {
    //             // объект со стилями для иконки закрытия, при null - иконки не будет
    //             opacity: 1
    //         },
    //         titleBar: popupTitle,
    //         closeByEsc: true, // закрытие окна по esc
    //         darkMode: false, // окно будет светлым или темным
    //         autoHide: true, // закрытие при клике вне окна
    //         draggable: true, // можно двигать или нет
    //         resizable: true, // можно ресайзить
    //         min_height: 100, // минимальная высота окна
    //         min_width: 100, // минимальная ширина окна
    //         lightShadow: true, // использовать светлую тень у окна
    //         angle: true, // появится уголок
    //         overlay: {
    //             // объект со стилями фона
    //             backgroundColor: 'black',
    //             opacity: 500
    //         },
    //         events: myBtnevents,
    //         // events: {
    //         //     onPopupClose: function(PopupWindow) {
    //         //         // Событие при закрытии окна
    //         //         PopupWindow.destroy()
    //         //     },
    //         //     onPopupShow: function() {
    //         //         // Событие при показе окна
    //         //         $('#add_servio_reserve').click(function () {
    //         //             console.log('modal show test!');
    //         //
    //         //         });
    //         //     },
    //         // }
    //     })
    //
    //
    //     // if(!myBtnevents)
    //     // {
    //         myBtnevents = {
    //             onPopupClose: function (PopupWindow) {
    //                 // Событие при закрытии окна
    //                 PopupWindow.destroy()
    //             },
    //         }
    //     // }
    //
    //     //вызов окна
    //     PopupProductProvider.show();
    // }

    makePopupV2(popupTechName,htmlContent,popupTitle,myBtnevents)
    {

        let PopupProductProvider = BX.PopupWindowManager.create(popupTechName, BX('element'), {
            content: htmlContent,
            width: 500, // ширина окна
            // height: 800, // высота окна
            zIndex: 100, // z-index
            closeIcon: {
                // объект со стилями для иконки закрытия, при null - иконки не будет
                opacity: 1
            },
            titleBar: popupTitle,
            autoHide: false,
            closeByEsc: true, // закрытие окна по esc
            darkMode: false, // окно будет светлым или темным
            autoHide: true, // закрытие при клике вне окна
            draggable: true, // можно двигать или нет
            resizable: true, // можно ресайзить
            min_height: 100, // минимальная высота окна
            min_width: 100, // минимальная ширина окна
            lightShadow: true, // использовать светлую тень у окна
            angle: true, // появится уголок
            overlay: {
                // объект со стилями фона
                backgroundColor: 'black',
                opacity: 500
            },
            events: myBtnevents,
            // events: {
            //     onPopupClose: function(PopupWindow) {
            //         // Событие при закрытии окна
            //         PopupWindow.destroy()
            //     },
            //     onPopupShow: function() {
            //         // Событие при показе окна
            //         $('#add_servio_reserve').click(function () {
            //             console.log('modal show test!');
            //
            //         });
            //     },
            // }
        })


        // if(!myBtnevents)
        // {
        myBtnevents = {
            onPopupClose: function (PopupWindow) {
                // Событие при закрытии окна
                PopupWindow.destroy()
            },
        }
        // }

        // вызов окна
        // PopupProductProvider.show();
        return PopupProductProvider
    }

    /*
    * Формирование даты
    * */
    createDate(dateObject)
    {
        let date = dateObject,
            month, day;

        if (date.getMonth() < 10) month = '0' + (date.getMonth() + 1);
        else month = date.getMonth() + 1;

        if (date.getDate() < 10) day = '0' + date.getDate();
        else day = date.getDate();

        return date.getFullYear() + '-' + month + '-' + day;
    }

    /*
    * Получение данных компании по ее коду из настроек модуля
    * */
    // getCompanyInfo()
    // {
    //     let self = this
    //
    //     this.makeAjaxRequest(this.url.ajax,{'ACTION' : 'GET_COMPANY_INFO'},
    //         function (response) {
    //
    //         // console.log('COMPANY  callback function from ajax',response)
    //         if(response.error)
    //         {
    //             self.list.errors.push(response.error);
    //             // console.log('Company Error!!!',response.error);
    //             // self.addErrorsBeforeForm(
    //             //     `
    //             //     <div class="ui-alert ui-alert-danger">
    //             //         <span class="ui-alert-message"><strong>Error!</strong> ${response.error}</span>
    //             //     </div>
    //             //     `
    //             // )
    //         }
    //         else
    //         {
    //             self.company = response.result
    //             self.filters.companyId = response.result.CompanyID
    //         }
    //     })
    //
    //     // console.log('errors list',this.list.errors);
    // }


    //добавление ошибок или success
    addErrorsBeforeFormNew(formObj,errText,flag)
    {
        if(formObj !== null)
        {
            if(flag === 'error')
            {
                $(formObj).before(`<div class="ui-alert ui-alert-danger custom-error">
                                <span class="ui-alert-message"><strong>Error!</strong> ${errText}</span>
                            </div>`);
            }
            else
            {
                $(formObj).before(`<div class="ui-alert ui-alert-success custom-success">
                                <span class="ui-alert-message"><strong>Error!</strong> ${errText}</span>
                            </div>`);
            }
        }
    }

    //добавление ошибок или success
    addErrorsBeforeForm(errText,flag)
    {
        let form = document.getElementById('servio_popup')

        if(form !== null)
        {
            if(flag === 'error')
            {
                $(form).before(`<div class="ui-alert ui-alert-danger custom-error">
                                <span class="ui-alert-message"><strong>Error!</strong> ${errText}</span>
                            </div>`);
            }
            else
            {
                $(form).before(`<div class="ui-alert ui-alert-success custom-success">
                                <span class="ui-alert-message"><strong>Error!</strong> ${errText}</span>
                            </div>`);
            }
        }
    }

    //удаление ошибок или success
    deleteErrorsinForm(windowObj)
    {
        if(windowObj !== null && windowObj instanceof Object === true)
        {
            let elems = windowObj.querySelectorAll('.custom-error, .custom-Success')
            if(elems.length > 0)
            {
                for(let notice of elems)
                {
                    notice.remove()
                }
            }
        }
    }

    // takeFormData()
    // {
    //     let form = document.getElementById('servio_popup'),
    //         elem
    //     if(form !== null)
    //     {
    //         for(elem of form.elements)
    //         {
    //             if(elem.name != '' && this.filters.hasOwnProperty(elem.name))
    //             {
    //                 this.filters[elem.name] = elem.value
    //             }
    //         }
    //     }
    //
    //     return this.filters
    // }

    // getRoomsByFilter()
    // {
    //     let data = {'ACTION' : 'GET_CATEGORIES_WITH_ROOMS', 'FIELDS':this.filters},
    //         self = this,
    //         priceBlock = document.getElementById('servio_price_info')
    //
    //     this.makeAjaxRequest(this.url.ajax,data,
    //         function (response) {
    //             console.log('ROOMS',response);
    //
    //             if(response.error === false)
    //             {
    //                 // let roomSelect = document.getElementById('roomCategory'),
    //                 //     options = '<option value="">Select...</option>';
    //
    //
    //                 if(response.result.rooms !== undefined)
    //                 {
    //                     // for(let option of response.result.rooms)
    //                     // {
    //                     //     options += `<option value="${option.Id}">${option.CategoryName} (${option.FreeRoom} rooms)</option>`
    //                     // }
    //
    //
    //                     // self.list.roomCategory = response.result.rooms;
    //                     // self.list.categoryPriceData = response.result.priceLists;
    //                     // self.showPriceLists(); //отображение на странице
    //
    //                     console.log('priceLists',response.result.rooms);
    //
    //                     // roomSelect.innerHTML = options
    //                     self.list.roomCategory = response.result.rooms;
    //                     self.ShowPicesAndSelect(response.result.rooms); //отображение на странице
    //                 }
    //                 else
    //                 {
    //                     self.list.errors.push(response.error);
    //                     if(priceBlock !== null)
    //                     {
    //                         priceBlock.innerHTML = ''
    //                     }
    //                     let err =
    //                         `
    //                          <div class="ui-alert ui-alert-danger">
    //                              <span class="ui-alert-message"><strong>Error!</strong>${response.error}</span>
    //                          </div>
    //                         `
    //                     self.addErrorsBeforeForm(err);
    //                 }
    //
    //             }
    //             else
    //             {
    //                 self.list.errors.push(response.error);
    //                 if(priceBlock !== null)
    //                 {
    //                     priceBlock.innerHTML = ''
    //                 }
    //
    //                 let err =
    //                     `
    //                          <div class="ui-alert ui-alert-danger custom-error">
    //                              <span class="ui-alert-message"><strong>Error! </strong>${response.error}</span>
    //                          </div>
    //                     `
    //                 self.addErrorsBeforeForm(err);
    //             }
    //
    //             console.log('Err',self.list.errors);
    //         })
    // }


    //замена функции getRoomsByFilter
    getRoomsByFilterNew()
    {
        let self = this,
            popup = document.getElementById('servio-hotel-reservation'),
            form = document.getElementById('servio_popup'),
            roomCategorySelect = document.getElementById('roomCategory'),
            priceTableBlock = document.getElementById('servio_price_info'),
            fields = {},  //данные формы
            data = {'ACTION' : 'GET_CATEGORIES_WITH_ROOMS',  'FIELDS': {}},
            roomOptions = '<option value="">Select...</option>',
            tableBody = '',
            priceTable = '',
            i = 1

        //удаление ошибоки др. уведомлений
        this.deleteErrorsinForm(popup)


        //данные из формы
        fields = this.getFromFieldsData(form)

        if(Object.keys(fields).length <= 0)
        {

            console.log('Error! Проблема с получением данных формы')
            //скрываем/отображаем кнопку резерва
            this.showReserveButton();
        }
        else
        {
            data.FIELDS = fields

            this.makeAjaxRequest(this.url.ajax,data,
                function (response) {
                    console.log('ROOMS NEW', response);

                    if(response.error !== false)
                    {
                        self.addErrorsBeforeForm(response.error,'error')


                        //очищение селекта комнат + списка цен
                        if(roomCategorySelect !== null)
                        {
                            roomCategorySelect.innerHTML = roomOptions
                        }

                        if(priceTableBlock !== null)
                        {
                            priceTableBlock.innerHTML = ''
                        }

                    }
                    else
                    {
                        if(response.result.rooms == null)
                        {
                            //на всякий случай
                            console.log('Что-то с результатом списка комнат, его нет!');
                            self.categoriesObj = {}
                        }
                        else
                        {

                            //сохраняем массив категорий с ценами
                            self.categoriesObj = response.result.rooms

                            Object.entries(response.result.rooms).forEach(([key, row]) => {
                                roomOptions += `<option value="${row.roomTypeId}">${row.roomTypeName} (${row.FreeRoom}  ${(row.FreeRoom > 1)  ? 'rooms' : 'room' } )</option>`

                                tableBody +=
                                    `<tr>
                                           <td>${i}</td>
                                           <td>${row['roomTypeName']}</td>
                                           <td>${row['dates']}</td>
                                           <td>${row.totalDays}</td>
                                           <td>${row.totalPrice}, ${row.currency}</td>
                                       </tr>`
                                i++
                            })

                            // console.log('Option',roomOptions);

                            priceTable =
                                `<table class="table table-sm table-responsive">
                                        <thead>
                                           <tr>
                                               <th scope="col-sm">#</th>
                                               <th scope="col-sm">Type</th>
                                               <th scope="col-sm">Dates</th>
                                               <th scope="col-sm">Days Total</th>
                                               <th scope="col-sm">Total Price</th>
                                           </tr>
                                        </thead>
                                        <tbody> ${tableBody}</tbody>
                                    </table>`

                            if(roomCategorySelect !== null)
                            {
                                roomCategorySelect.innerHTML = roomOptions
                            }

                            if(priceTableBlock !== null)
                            {
                                priceTableBlock.innerHTML = priceTable
                            }

                        }
                    }

                    self.showReserveButton();

                })


        }

        // console.log('Получаем данные формы для обновления данных по номерам',data );
    }

    getFromFieldsData(formObj)
    {
        let fields = {}
        if(formObj !== null)
        {
            Object.entries(formObj.elements).forEach(([index,elem]) => {
                if(elem.name !== '')
                {

                    fields[elem.name] = elem.value

                    if(elem.type === 'checkbox')
                    {
                        fields[elem.name] = ( elem.checked === true ) ? 1  : 0
                    }
                }


            });
        }
        else
        {
            'ERRoR custom! WRONG Form Object or Type!'
        }

        return fields
    }

    // ShowPicesAndSelect(priceRowsArr)
    // {
    //     let i = 1, tableHtml = '', body = '', row,key,value,
    //         // parentForm = document.getElementById('test_table')
    //         parentForm = document.getElementById('servio_price_info'),
    //         roomSelect = document.getElementById('roomCategory'),
    //         options = '<option value="">Select...</option>'
    //
    //
    //     // console.log('res',priceRowsArr);
    //
    //
    //     // console.log('row',Object.keys(priceRowsArr).length);
    //
    //     if(Object.keys(priceRowsArr).length > 0)
    //     {
    //         // for(row in priceRowsArr)
    //         Object.entries(priceRowsArr).forEach(([key, row]) => {
    //             // console.log('row',key,row);
    //
    //             options += `<option value="${row.roomTypeId}">${row.roomTypeName} (${row.FreeRoom} rooms)</option>`
    //
    //             body +=
    //                 `
    //                 <tr>
    //                     <td>${i}</td>
    //                     <td>${row['roomTypeName']}</td>
    //                     <td>${row['dates']}</td>
    //                     <td>${row.totalDays}</td>
    //                     <td>${row.totalPrice}, ${row.currency}</td>
    //                 </tr>
    //                 `
    //             i++
    //         })
    //
    //         tableHtml =
    //             `
    //             <table class="table table-sm table-responsive">
    //                 <thead>
    //                     <tr>
    //                         <th scope="col-sm">#</th>
    //                         <th scope="col-sm">Type</th>
    //                         <th scope="col-sm">Dates</th>
    //                         <th scope="col-sm">Days Total</th>
    //                         <th scope="col-sm">Total Price</th>
    //                     </tr>
    //                 </thead>
    //                 <tbody> ${body}</tbody>
    //             </>
    //             `
    //
    //         if(parentForm !== null)
    //         {
    //             // parentForm.innerHTML = ''
    //             parentForm.innerHTML = tableHtml
    //         }
    //         if(roomSelect !== null)
    //         {
    //             // parentForm.innerHTML = ''
    //             roomSelect.innerHTML = options
    //         }
    //
    //     }
    // }

    //добавление цен в массив
    // showPriceLists()
    // {
    //
    //     let i = 1, priceList, roomCategory, servise, price, tableHtml = '', tableBodyHtml = '', tableServises = ''
    //
    //     //прайсы + валюта
    //     if(Object.keys(this.list.categoryPriceData).length > 0)
    //     {
    //
    //         //прайсы
    //         for(priceList of this.list.categoryPriceData.PriceLists)
    //         {
    //             // console.log(1,priceList)
    //
    //             // tableBodyHtml += `
    //             //         <tr>
    //             //             <td> ${i}</td>
    //             //             <td rowspan="${priceList.RoomTypes.length}">${priceList.PriceListID}</td>
    //             //             <td>${(!priceList.IsNonReturnRate) ? 'Yes' : 'No'}</td>
    //             //             <td>${(priceList.IsSpecRate) ? 'Yes' : 'No'}</td>
    //             //         `
    //
    //             //категории комнат
    //             for(roomCategory of priceList.RoomTypes)
    //             {
    //                 // console.log(2,roomCategory)
    //
    //                 //сервисы
    //
    //                 tableServises += `<div class="row">`
    //                 for(servise of roomCategory.Services)
    //                 {
    //                     // console.log(3,servise);
    //
    //                     tableServises +=
    //                         `
    //                         <table width="100%">
    //                             <thead>
    //                                 <th>Date</th>
    //                                 <th>Servise</th>
    //
    //                                 <th>Price</th>
    //                                 <th>Total</th>
    //                             </thead>
    //                         </table>
    //                         `
    //                 }
    //                 tableServises += `</div>`
    //
    //                 tableBodyHtml += `
    //
    //                     <tr>
    //                         <td> ${i}</td>
    //                         <td>${priceList.PriceListID}</td>
    //                         <td>${(!priceList.IsNonReturnRate) ? 'Yes' : 'No'}</td>
    //                         <td>${(priceList.IsSpecRate) ? 'Yes' : 'No'}</td>
    //
    //
    //
    //                         <td>${roomCategory.ID}</td>
    //                         <td>${roomCategory.SaleRestrictions.MinPay.Days}</td>
    //                         <td>${(roomCategory.SaleRestrictions.MinStay.Days > 0) ? roomCategory.SaleRestrictions.MinStay.Days : ''}</td>
    //                         <td>
    //                             <div class="row">
    //                                 <div class="col-6">SSSS</div>
    //                                 <div class="col-6">DDDDD</div>
    //                             </div>
    //
    //                         </td>
    //
    //
    //
    //                     </tr>
    //                     `
    //
    //                 i++
    //             }
    //
    //             // tableBodyHtml += `<tr>`
    //
    //
    //
    //         }
    //
    //
    //         tableHtml =
    //             `
    //                 <table class="table table-sm table-responsive">
    //                     <thead>
    //                         <tr>
    //                             <th scope="col-sm">#</th>
    //                             <th scope="col-sm">Price List Id</th>
    //                             <th scope="col-sm">Returnable</th>
    //                             <th scope="col-sm">Special Rate</th>
    //                             <th scope="col-sm">Room Type</th>
    //                             <th scope="col-sm">Min Pay Days</th>
    //                             <th scope="col-sm">Min Stay Days</th>
    //                             <th scope="col-sm">Servises</th>
    //                         </tr>
    //                     </thead>
    //                     <tbody>
    //             ` + tableBodyHtml  +
    //             `       </tbody>
    //                 <table>
    //             `
    //
    //         let parentForm = document.getElementById('test_table');
    //
    //         if(parentForm !== null)
    //         {
    //             parentForm.innerHTML = tableHtml
    //         }
    //     }
    //     console.log('Prices Lists',this.list.categoryPriceData);
    //     // console.log('Prices Lists',tableHtml);
    // }




    //!!!Изменение какого-либо фильтра

    //изменение даты "С"
    // changeDateStart()
    // {
    //     let startDateField = document.getElementById('dateFrom'),
    //         finishDateField = document.getElementById('dateTo'),
    //         finishDate
    //
    //     if(startDateField !== null && finishDateField !== null)
    //     {
    //         if(startDateField.value !== '')
    //         {
    //             if(startDateField.value == finishDateField.value)
    //             {
    //                 finishDate = new Date(startDateField.value)
    //                 finishDate.setDate(finishDate.getDate()  + 1)
    //                 finishDateField.value = this.createDate(finishDate);
    //             }
    //         }
    //
    //         //ОБНОВЛЕНИЕ ПОЛЕЙ ОБЪЕКТА
    //         this.takeFormData();
    //
    //         this.getRoomsByFilter()
    //     }
    // }

    // changeFinishDate()
    // {
    //     let self = this,
    //         startDateField = document.getElementById('dateFrom'),
    //         finishDateField = document.getElementById('dateTo'),
    //         startDate
    //
    //     if(startDateField !== null && finishDateField !== null)
    //     {
    //         if(finishDateField.value !== '' && finishDateField.value === startDateField.value)
    //         {
    //             startDate = new Date(finishDateField.value);
    //             startDate.setDate(startDate.getDate() - 1);
    //             startDateField.value = this.createDate(startDate);
    //         }
    //     }
    //
    //     //ОБНОВЛЕНИЕ ПОЛЕЙ ОБЪЕКТА
    //     this.takeFormData();
    //
    //     this.getRoomsByFilter();
    // }

    //Изменеие взрослых и детей
    // changeTextFields()
    // {
    //     //ОБНОВЛЕНИЕ ПОЛЕЙ ОБЪЕКТА
    //     this.takeFormData();
    //     this.getRoomsByFilter()
    // }

    //удаляет все, кроме цифрв полях взрослых и детей
    // clearAllExeptnums(obj)
    // {
    //     obj.value = Number(obj.value.replace(/[^\d]/g,''))
    // }

    showReserveButton()
    {

        // console.log('CATEGORIES ARR',this.categoriesObj);

        let reserveButton = document.getElementById('add_servio_reserve'),
            formData = {}


        formData = this.getFromFieldsData(document.getElementById('servio_popup'))

        // console.log('deal id: ',this.deal);
        // console.log('reserveButton: ',reserveButton);
        // console.log('fields: ',formData);

        if(reserveButton !== null && Object.keys(formData).length > 0)
        {
            //резерв не позволителет если id сделки === 0 (сделка не существует еще)
            if(
                this.deal.id > 0
                &&
                Number(formData.roomCategory) > 0
                &&
                formData.dateFrom != ''
                &&
                formData.dateTo != ''
                &&
                formData.adults !== ''
                &&
                formData.childs !==  ''
                &&
                formData.paidType !==  ''
            )
            {
                reserveButton.style.display = 'inline-flex';
            }
            else
            {
                reserveButton.style.display = 'none';
            }
        }
        else
        {
            reserveButton.style.display = 'none';
        }

    }

    //создание резерва
    addReservation(popupObj)
    {
        let self = this,
            formData = {},
            popup = document.getElementById('servio_popup')

        formData = this.getFromFieldsData(popup)

        // console.log('On Reservation',formData);
        // console.log('On Reservation Categories',this.categoriesObj)


        // страхуемся
        if(
            this.deal.id > 0
            &&
            Number(formData.roomCategory) > 0
            &&
            formData.dateFrom != ''
            &&
            formData.dateTo != ''
            &&
            formData.adults !== ''
            &&
            formData.childs !==  ''
            &&
            formData.paidType !==  ''
        )
        {
            // console.log('ADD Reserve',this.list.roomCategory);
            if(formData.roomCategory != '' && this.categoriesObj.hasOwnProperty(formData.roomCategory))
            {
                this.makeAjaxRequest(this.url.ajax,
                    {
                        'ACTION' : 'ADD_RESERVE',
                        'FIELDS' : {
                            'FILTERS': formData,
                            'ROOM_CATEGORY' : this.categoriesObj[formData.roomCategory],
                            'DEAL_ID' : this.deal.id,
                        }

                    },
                    function (response) {
                        console.log('Reserve Response',response)

                        // if(response.error !== false)
                        // {
                        //     self.addErrorsBeforeForm(response.error,'error')
                        // }
                        // else
                        // {
                        //     let form = document.getElementById('servio_popup')
                        //     if(form !== null)
                        //     {
                        //         self.addErrorsBeforeForm(`Создана бронь № ${response.result}`,'success');
                        //
                        //         // setTimeout(()=>{
                        //         //     popupObj.destroy()
                        //         //     self.loadServioReservePopup()
                        //         // },5000)
                        //     }
                        // }
                    })
            }
            else
            {
                //some custom error
            }



        }
        else
        {
            console.log('Reserve will not create!!!');
        }

    }

    // loadServioReserveFormPopup()
    // {
    //     let self = this,
    //         html = `<form id="servio_popup" onsubmit="return false" autocomplete="off"></form>`,
    //         servioBtn = document.getElementById('servio'),
    //         dateFrom = '',
    //         dateTo = '',
    //         dateStart = new Date(),
    //         dateFinish = new Date(),
    //         adults = 1,
    //         childs = 0,
    //         companyObj = {}, //для хранения данных о компании
    //         companyName = '', //навзание нашей компании
    //         // categoriesObj = {}, //для хранения данных категорий для дополнения селектовпри выборе
    //         roomOptions = '',
    //         startData = {}, // для передачи стартовых дат и людей,
    //         popupObj = {}, //объект для попапа
    //         popupBtnevents = {}, //объект с кнопками попапа
    //         formHtml = ``
    //
    //
    //     //задаем даты и ограничения
    //     dateFinish.setDate(dateFinish.getDate()  + 1);
    //     dateFrom = this.createDate(dateStart)
    //     dateTo = this.createDate(dateFinish)
    //     //задаем даты и ограничения
    //
    //
    //
    //
    //     //создаем базовый массив для получения категорий при загрузке
    //     startData = {
    //         dateFrom : dateFrom,
    //         dateTo : dateTo,
    //         adults : adults,
    //         childs : childs,
    //         //companyId : 0 //это нужно было бы передавать, но я в php присваиваю
    //     }
    //
    //
    //     console.log('NEW FORM OPEN',startData);
    //
    //     if(servioBtn !== null)
    //     {
    //         servioBtn.onclick = () => {
    //
    //             //получаем данные резерва
    //             this.makeAjaxRequest(this.url.ajax, {'ACTION': 'GET_DATA_FOR_FORM', 'FIELDS': startData},
    //                 function (response) {
    //                     console.log('TEST NEW POPUP', response)
    //
    //                     popupObj = self.makePopupV2('servio-hotel-reservation',html,'Hotel Reservation',popupBtnevents)
    //                     let popupBody = document.getElementById('servio_popup')
    //
    //                     if(response.errors.length > 0)
    //                     {
    //                         console.log('Load Form Data Error',response.errors)
    //                         for(let err of response.errors)
    //                         {
    //                             formHtml +=
    //                                 `
    //                                  <div class="ui-alert ui-alert-danger custom-error">
    //                                     <span class="ui-alert-message"><strong>Error! </strong>${err}</span>
    //                                 </div>
    //                                 `
    //                         }
    //
    //                         popupBody.innerHTML = formHtml
    //                     }
    //                     else
    //                     {
    //
    //                         let tableBody = ``,
    //                             priceTable = ``,
    //                             i = 1,
    //                             companyId = 0
    //
    //                         if(response.categories.rooms !== 'undefined')
    //                         {
    //                             //сохраняем массив категорий с ценами
    //                             self.categoriesObj = response.categories.rooms
    //
    //
    //                             roomOptions = '<option>Select...</option>'
    //                             Object.entries(self.categoriesObj).forEach(([key, row]) => {
    //                                 roomOptions += `<option value="${row.roomTypeId}">${row.roomTypeName} (${row.FreeRoom}  ${(row.FreeRoom > 1)  ? 'rooms' : 'room' } )</option>`
    //                                 // console.log('1  Cat',row);
    //
    //                                 tableBody +=
    //                                     `<tr>
    //                                        <td>${i}</td>
    //                                        <td>${row['roomTypeName']}</td>
    //                                        <td>${row['dates']}</td>
    //                                        <td>${row.totalDays}</td>
    //                                        <td>${row.totalPrice}, ${row.currency}</td>
    //                                    </tr>`
    //                                 i++
    //                             })
    //
    //                             priceTable =
    //                                 `<table class="table table-sm table-responsive">
    //                                     <thead>
    //                                        <tr>
    //                                            <th scope="col-sm">#</th>
    //                                            <th scope="col-sm">Type</th>
    //                                            <th scope="col-sm">Dates</th>
    //                                            <th scope="col-sm">Days Total</th>
    //                                            <th scope="col-sm">Total Price</th>
    //                                        </tr>
    //                                     </thead>
    //                                     <tbody> ${tableBody}</tbody>
    //                                 </table>`
    //
    //                         }
    //                         else
    //                         {
    //                             self.categoriesObj = {}
    //                         }
    //
    //                         if(Object.keys(response.company).length > 0)
    //                         {
    //                             companyObj = response.company
    //                             companyId = response.company.CompanyID
    //                             companyName = response.company.CompanyName
    //                         }
    //                         else
    //                         {
    //                             companyObj = {}
    //                         }
    //
    //                         popupBody.innerHTML =
    //                             `<div class="form-row">
    //                                 <div class="col-sm-6 form-group">
    //                                     <label for="dateFrom" class="col-form-label-sm">Date From</label>
    //                                     <input type="date" name="dateFrom" class="form-control form-control-sm tm-popup-task-form-textbox bx-focus" id="dateFrom" title="Select date from" autocomplete="off"
    //                                         value="${dateFrom}"
    //                                         min="${dateFrom}"
    //                                         >
    //                                 </div>
    //                                 <div class="col-sm-6 form-group">
    //                                     <label for="dateTo" class="col-form-label-sm">Date To</label>
    //                                     <input type="date" name="dateTo" class="form-control form-control-sm tm-popup-task-form-textbox bx-focus" id="dateTo" title="Select date from" autocomplete="off"
    //                                          value="${dateTo}"
    //                                          min="${dateTo}"
    //                                         >
    //                                 </div>
    //                             </div>
    //
    //                             <div class="form-row">
    //                                 <div class="form-group col-sm">
    //                                     <label for="adults">Adults</label>
    //                                     <input id="adults" name="adults" class="form-control form-control-sm tm-popup-task-form-textbox bx-focus"
    //                                            value="${adults}"
    //                                            >
    //                                 </div>
    //                             </div>
    //
    //                             <div class="form-row">
    //                                 <div class="form-group col-sm">
    //                                     <label for="childs">Childs</label>
    //                                     <input id="childs" name="childs" class="form-control form-control-sm tm-popup-task-form-textbox bx-focus"
    //                                             value="${childs}"
    //                                            >
    //                                 </div>
    //                             </div>
    //
    //                             <div class="form-row">
    //                                <div class="form-group col-sm">
    //                                    <label for="roomCategory">Room Category</label>
    //                                    <select id="roomCategory" name="roomCategory" class="form-control form-control-sm tm-popup-task-form-textbox bx-focus">
    //                                    ${roomOptions}
    //                                    </select>
    //                                </div>
    //                             </div>
    //
    //                              <div class="form-row">
    //                                <div class="form-group col-sm">
    //                                    <label for="paidType">Paid Type</label>
    //                                    <select id="paidType" name="paidType" class="form-control form-control-sm tm-popup-task-form-textbox bx-focus">
    //                                        <option value="">Select...</option>
    //                                        <option value="100">Cash</option>
    //                                        <option value="200">Credit Card</option>
    //                                        <option value="300">Private Payment</option>
    //                                    </select>
    //                                </div>
    //                             </div>
    //
    //                             <input type="hidden" name="companyId" value="${companyId}">
    //                             <input type="hidden" name="companyName" value="${companyName}">
    //
    //                             <div id="servio_price_info" class="text-center">
    //                                ${priceTable}
    //                             </div>
    //
    //                              <button type="button" id="add_servio_reserve" class="mt-2 ui-btn ui-btn-danger-dark ui-btn-icon-task ui-btn-round">
    //                                 Reserve!
    //                              </button>`
    //
    //
    //
    //
    //                         //Здесь реакция на изменения полей и обновление цен + категорий
    //
    //                         //  1,2  Изменение поля dateFrom и dateTo
    //                         let dateFromInput = document.getElementById('dateFrom'),
    //                             dateToInput = document.getElementById('dateTo')
    //
    //                         if(dateFromInput !== null && dateToInput !== null)
    //                         {
    //                             dateFromInput.onchange = () => {
    //
    //                                 let startDate = new Date(dateFromInput.value),
    //                                     finishDate = new Date(dateToInput.value)
    //
    //                                 if(startDate >= finishDate)
    //                                 {
    //                                     finishDate.setDate(startDate.getDate() + 1)
    //                                     dateToInput.value = self.createDate(finishDate)
    //                                 }
    //
    //                                 //обновление цен  +  селекта комнат
    //                                 self.getRoomsByFilterNew()
    //                                 // self.showReserveButton();
    //                             }
    //
    //                             dateToInput.onchange = () => {
    //                                 let startDate = new Date(dateFromInput.value),
    //                                     finishDate = new Date(dateToInput.value)
    //                                 if(startDate >= finishDate)
    //                                 {
    //                                     startDate.setDate(finishDate.getDate() - 1)
    //                                     dateFromInput.value = self.createDate(startDate)
    //                                 }
    //
    //                                 //обновление цен  +  селекта комнат
    //                                 self.getRoomsByFilterNew()
    //                                 // self.showReserveButton();
    //                             }
    //                         }
    //
    //
    //
    //
    //                         //4. Изменение Селекта
    //                         let roomsCategorySelect = document.getElementById('roomCategory')
    //                         if(roomsCategorySelect !== null)
    //                         {
    //                             roomsCategorySelect.onchange =  function () {
    //                                 self.showReserveButton();
    //                             }
    //                         }
    //
    //
    //                         // 6,7 Изменение значений в полях Adults и Childs
    //                         let adultsAndChildFields = document.querySelectorAll('#adults, #childs')
    //                         if(adultsAndChildFields.length > 0)
    //                         {
    //                             // let roomCatField = document.getElementById('roomCategory')
    //                             for(let elem of adultsAndChildFields)
    //                             {
    //                                 //запрос категорий при изменении значений полей
    //                                 elem.onchange = function () {
    //                                     self.getRoomsByFilterNew()
    //                                     // self.showReserveButton();
    //                                 }
    //
    //                                 //удаление из полей всего кроме цифр
    //                                 elem.onkeyup = function () {
    //                                     this.value = Number(this.value.replace(/[^\d]/g,''))
    //                                 }
    //                             }
    //                         }
    //
    //
    //                         // 8. Изменение селекта  типа оплат
    //                         let paidTypeField = document.getElementById('paidType')
    //                         if(paidTypeField !== null)
    //                         {
    //                             paidTypeField.onchange = () => {
    //                                 self.showReserveButton();
    //                             }
    //                         }
    //
    //
    //                         // 9. Резервирование
    //                         let reserveButton = document.getElementById('add_servio_reserve')
    //                         if(reserveButton !== null)
    //                         {
    //                             reserveButton.onclick = () => {
    //                                 self.addReservation()
    //                             }
    //                         }
    //
    //                     }
    //
    //
    //
    //                     popupObj.show();
    //                 })
    //         }
    //     }
    //
    //
    // }


    loadReservePopupV4()
    {
        let self = this,
            servioBtn = document.getElementById('servio'),
            dateStart = new Date(),
            dateFinish = new Date(),
            dateFrom = '',
            dateTo = '',
            popupObj = {},
            html = ``,
            company = {
                id : 0,
                name : ''
            }



        //задаем даты и ограничения
        dateFinish.setDate(dateFinish.getDate()  + 1);
        dateFrom = this.createDate(dateStart)
        dateTo = this.createDate(dateFinish)
        //задаем даты и ограничения

        html =
            `<form id="servio_popup" onsubmit="return false" autocomplete="off">
                <div class="form-row">
                    <div class="col-sm-6 form-group">
                        <label for="dateFrom" class="col-form-label-sm">Date From</label>
                        <input type="date" name="dateFrom" class="form-control form-control-sm tm-popup-task-form-textbox bx-focus" id="dateFrom" title="Select date from" autocomplete="off"
                            value="${dateFrom}"
                            min="${dateFrom}"
                            >
                    </div>
                    <div class="col-sm-6 form-group">
                        <label for="dateTo" class="col-form-label-sm">Date To</label>
                        <input type="date" name="dateTo" class="form-control form-control-sm tm-popup-task-form-textbox bx-focus" id="dateTo" title="Select date from" autocomplete="off"
                             value="${dateTo}"
                             min="${dateTo}"
                            >
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-sm">
                        <label for="adults">Adults</label>
                        <input id="adults" name="adults" class="form-control form-control-sm tm-popup-task-form-textbox bx-focus"
                            value="1"
                        >
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group col-sm">
                        <label for="childs">Childs</label>
                        <input id="childs" name="childs" class="form-control form-control-sm tm-popup-task-form-textbox bx-focus"
                             value="0"
                        >
                    </div>
                </div>
                
                 <div class="form-row hidden-input">
                    <div class="form-group col-sm">
                        <label for="childAges">Child Ages</label>
                        <input id="childAges" name="childAges" class="form-control form-control-sm tm-popup-task-form-textbox bx-focus">
                    </div>
                </div>
                
                <div class="form-row">
                <div class="form-group col-sm">
                   <label for="hootelId">Hotel</label>
                   <select id="hootelId" name="hootelId" class="form-control form-control-sm tm-popup-task-form-textbox bx-focus" disabled>
                       <option value="">Select...</option>
                       <option value="1" selected>Hotel 1</option>
                   </select>
                </div>
                </div>
                                
                <div class="form-row">
                <div class="form-group col-sm">
                   <label for="paidType">Paid Type</label>
                   <select id="paidType" name="paidType" class="form-control form-control-sm tm-popup-task-form-textbox bx-focus">
                       <option value="">Select...</option>
                       <option value="100">Cash</option>
                       <option value="200">Credit Card</option>
                       <option value="300">Private Payment</option>
                   </select>
                </div>
                </div>
                
                
               <div class="form-row">
                   <div class="form-group col-sm">
                        <label for="lpAuthCode">Loyality Programm Code</label>
                        <input id="lpAuthCode" name="lpAuthCode" class="form-control form-control-sm tm-popup-task-form-textbox bx-focus">
                   </div>
               </div>
                
               <div class="form-group form-check">
                    <input type="checkbox" class="form-check-input" id="extraBed" name="extraBed" value="">
                    <label class="form-check-label" for="extraBed">Need Extra Bed</label>
               </div>
                
               <div class="form-group form-check">
                    <input type="checkbox" class="form-check-input" id="transport" name="transport" value="">
                    <label class="form-check-label" for="transport">Need Transport</label>
               </div>
                
               <div class="form-group form-check">
                    <input type="checkbox" class="form-check-input" id="touristTax" name="touristTax" value="">
                    <label class="form-check-label" for="touristTax">Tourist Tax</label>
               </div>
                
                <!--<div class="form-row">-->
                   <!--<div class="form-group col-sm">-->
                       <!--<label for="roomCategory">Room Category</label>-->
                       <!--<select id="roomCategory" name="roomCategory" class="form-control form-control-sm tm-popup-task-form-textbox bx-focus">-->
                           <!--<option value="">Select...</option>-->
                       <!--</select>-->
                   <!--</div>-->
                <!--</div>-->
                
                
               <input type="hidden" name="companyId" id="companyId" value="">
               <input type="hidden" name="companyName" id="companyName" value="">
                <input type="hidden" name="companyCodeId" id="companyCodeId" value="">
                
               <div class="ui-btn-container ui-btn-container-center text-right">
                  <button type="button" id="servio_search" class="mt-2 ui-btn ui-btn-primary-dark ui-btn-right ui-btn-icon-search">
                      Search
                  </button>
               </div>
                
                  
               <div id="servio_price_info" class="text-center">
                   
               </div>
                  
               <button type="button" id="add_servio_reserve" class="mt-2 ui-btn ui-btn-danger-dark ui-btn-icon-cloud">
                  Reserve!
               </button>
            </form>`

        if(servioBtn !== null) {
            //формируем popup с формой
            popupObj = self.makePopupV2('servio-hotel-reservation', html, 'Hotel Reservation', {})

            let form = document.getElementById('servio_popup')

            this.makeAjaxRequest(this.url.ajax,{'ACTION' : 'GET_COMPANY_INFO'},
                function (response) {
                    console.log('COMPANY RESULT', response);

                    if (response.error != false) {
                        // self.addErrorsBeforeForm(response.error, 'error')
                        self.addErrorsBeforeFormNew(form,response.error, 'error')
                    }
                    else {
                        company.id = response.result.CompanyID
                        company.name = response.result.CompanyName


                        let companyIdField = document.getElementById('companyId'),
                            companyNameField = document.getElementById('companyName'),
                            companyCodeIdField = document.getElementById('companyCodeId')

                        if(companyIdField !== null)
                        {
                            companyIdField.value = response.result.CompanyID;
                        }
                        if(companyNameField !== null)
                        {
                            companyNameField.value = response.result.CompanyName;
                        }
                        if(companyCodeIdField !== null)
                        {
                            companyCodeIdField.value = response.result.CompanyCodeID;
                        }
                    }


                    //здесь реакция на изменение полей

                    //  1,2  Изменение поля dateFrom и dateTo
                    let dateFromInput = document.getElementById('dateFrom'),
                        dateToInput = document.getElementById('dateTo')

                    if(dateFromInput !== null && dateToInput !== null)
                    {
                        dateFromInput.onchange = () => {

                            let startDate = new Date(dateFromInput.value),
                                finishDate = new Date(dateToInput.value)

                            if(startDate >= finishDate)
                            {
                                finishDate.setDate(startDate.getDate() + 1)
                                dateToInput.value = self.createDate(finishDate)
                            }

                            //обновление цен  +  селекта комнат
                            // self.getRoomsByFilterNew()
                            // self.showReserveButton();
                        }

                        dateToInput.onchange = () => {
                            let startDate = new Date(dateFromInput.value),
                                finishDate = new Date(dateToInput.value)
                            if(startDate >= finishDate)
                            {
                                startDate.setDate(finishDate.getDate() - 1)
                                dateFromInput.value = self.createDate(startDate)
                            }

                            //обновление цен  +  селекта комнат
                            // self.getRoomsByFilterNew()
                            // self.showReserveButton();
                        }
                    }


                    // 3,4 Изменение значений в полях Adults и Childs
                    let adultsAndChildFields = document.querySelectorAll('#adults, #childs'),
                        chuldAgesField = document.getElementById('childAges')

                    if(adultsAndChildFields.length > 0)
                    {
                        // let roomCatField = document.getElementById('roomCategory')
                        for(let elem of adultsAndChildFields)
                        {
                            //запрос категорий при изменении значений полей
                            elem.onchange = function () {
                                // self.getRoomsByFilterNew()
                                // self.showReserveButton();

                                if(elem.name === 'childs')
                                {
                                    if(chuldAgesField !== null)
                                    {
                                        if(elem.value != 0)
                                        {
                                            chuldAgesField.closest('.form-row ').classList.remove('hidden-input')
                                        }
                                        else
                                        {
                                            chuldAgesField.value = ''
                                            chuldAgesField.closest('.form-row ').classList.add('hidden-input')
                                        }
                                    }
                                }
                            }

                            //удаление из полей всего кроме цифр
                            elem.onkeyup = function () {
                                this.value = Number(this.value.replace(/[^\d]/g,''))

                                if(this.name === 'adults' && this.value == 0)
                                {
                                    this.value = 1
                                }
                            }
                        }
                    }

                    //изменение поля возраста детей
                    if(chuldAgesField !== null)
                    {
                        chuldAgesField.onkeyup = () =>
                        {
                            // chuldAgesField.value = Number(chuldAgesField.value.replace(/[^\s\d]+/g,''))
                            chuldAgesField.value = chuldAgesField.value.replace(/[^\s\d]+/g,'')
                            if(chuldAgesField.value == 0)
                            {
                                chuldAgesField.value = ''
                            }
                        }

                        chuldAgesField.onchange = () =>
                        {
                            chuldAgesField.value = chuldAgesField.value.trimRight()
                            // console.log('test Ages!', chuldAgesField.value);
                        }
                    }


                    //нажатие на поиск
                    let searchBtn = document.getElementById('servio_search')
                    if(searchBtn !== null)
                    {
                        //для очистки ошибок и сообщений
                        const popupContent = document.getElementById('popup-window-content-servio-hotel-reservation')

                        searchBtn.onclick = () => {
                            // получаем данные формы и валидируем их
                            let fields = self.getFromFieldsData(form)
                            console.log('fORM dATA',fields);


                            //удаление ошибок и сообщений
                            self.deleteErrorsinForm(popupContent);
                            //бореры нормального цвета
                            let formFileldsElems = form.querySelectorAll('input,select,textarea')
                            if(formFileldsElems.length > 0)
                            {
                                for(let inptField of formFileldsElems)
                                {
                                    if(inptField.classList.contains('my-error-field'))
                                    {
                                        inptField.classList.remove('my-error-field')
                                    }
                                }
                            }


                            //валидация

                            // console.log('iii',fields.childs,fields.childAges.split(' '), Number(fields.childs) == fields.childAges.split(' ').length);

                            if(
                                fields.dateFrom == '' || fields.dateTo == '' || fields.adults == '' || fields.childs == ''
                                ||
                                (
                                    Number(fields.childs) > 0 &&
                                    (
                                        fields.childAges.trim().length == 0 ||
                                        Number(fields.childs) != fields.childAges.trim().split(' ').length
                                    )
                                )
                            )
                            {
                                if(fields.dateFrom == '' )
                                {
                                    let df = form.querySelector('#dateFrom')
                                    df.classList.add('my-error-field')
                                    self.addErrorsBeforeFormNew(form,`Fill Date From field!`, 'error')
                                }
                                if(fields.dateTo == '' )
                                {
                                    let dt = form.querySelector('#dateTo')
                                    dt.classList.add('my-error-field')
                                    self.addErrorsBeforeFormNew(form,`Fill Date To field!`, 'error')
                                }
                                if(fields.adults == '' )
                                {
                                    let adt = form.querySelector('#adults')
                                    adt.classList.add('my-error-field')
                                    self.addErrorsBeforeFormNew(form,`Set adults number > 0!`, 'error')
                                }
                                if(fields.childs == '' )
                                {
                                    let chlds = form.querySelector('#childs')
                                    chlds.classList.add('my-error-field')
                                    self.addErrorsBeforeFormNew(form,`Set childs number or 0!`, 'error')
                                }
                                if((Number(fields.childs) > 0 && fields.childAges.trim().length == 0 || Number(fields.childs) != fields.childAges.trim().split(' ').length))
                                {
                                    let chlds = form.querySelector('#childs'),
                                        chldsAgs = form.querySelector('#childAges')
                                    chlds.classList.add('my-error-field')
                                    chldsAgs.classList.add('my-error-field')
                                    self.addErrorsBeforeFormNew(form,`Number of childs must be equal numbers in child ages!`, 'error')
                                }
                            }

                            else
                            {

                                //ajax для получения цен

                                self.makeAjaxRequest(self.url.ajax,{ACTION : 'GET_PRICES_BY_FILTER',FIELDS : fields},
                                    function (response) {

                                        console.log('GET PRICES LAST',response);

                                    })

                            }

                        }

                    }


                })


            servioBtn.onclick = () => {
                popupObj.show()
            }
        }

    }

    loadServioReserveFormPopupV3()
    {

        let self = this,
            servioBtn = document.getElementById('servio'),
            popupBtnevents = {},
            dateStart = new Date(),
            dateFinish = new Date(),
            dateFrom = '',
            dateTo = '',
            popupObj = {},
            html = ``,
            company = {
                id : 0,
                name : ''
            }

        //задаем даты и ограничения
        dateFinish.setDate(dateFinish.getDate()  + 1);
        dateFrom = this.createDate(dateStart)
        dateTo = this.createDate(dateFinish)
        //задаем даты и ограничения


        html=
            `<form id="servio_popup" onsubmit="return false" autocomplete="off">
                <div class="form-row">
                    <div class="col-sm-6 form-group">
                        <label for="dateFrom" class="col-form-label-sm">Date From</label>
                        <input type="date" name="dateFrom" class="form-control form-control-sm tm-popup-task-form-textbox bx-focus" id="dateFrom" title="Select date from" autocomplete="off"
                            value="${dateFrom}"
                            min="${dateFrom}"
                            >
                    </div>
                    <div class="col-sm-6 form-group">
                        <label for="dateTo" class="col-form-label-sm">Date To</label>
                        <input type="date" name="dateTo" class="form-control form-control-sm tm-popup-task-form-textbox bx-focus" id="dateTo" title="Select date from" autocomplete="off"
                             value="${dateTo}"
                             min="${dateTo}"
                            >
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-sm">
                        <label for="adults">Adults</label>
                        <input id="adults" name="adults" class="form-control form-control-sm tm-popup-task-form-textbox bx-focus"
                               value="0"
                               >
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group col-sm">
                        <label for="childs">Childs</label>
                        <input id="childs" name="childs" class="form-control form-control-sm tm-popup-task-form-textbox bx-focus"
                                value="0"
                               >
                    </div>
                </div>
                
                <div class="form-row">
                   <div class="form-group col-sm">
                       <label for="roomCategory">Room Category</label>
                       <select id="roomCategory" name="roomCategory" class="form-control form-control-sm tm-popup-task-form-textbox bx-focus">
                           <option value="">Select...</option>
                       </select>
                   </div>
                </div>
                
                 <div class="form-row">
                   <div class="form-group col-sm">
                       <label for="paidType">Paid Type</label>
                       <select id="paidType" name="paidType" class="form-control form-control-sm tm-popup-task-form-textbox bx-focus">
                           <option value="">Select...</option>
                           <option value="100">Cash</option>
                           <option value="200">Credit Card</option>
                           <option value="300">Private Payment</option>
                       </select>
                   </div>
                </div>
                
                <input type="hidden" name="companyId" id="companyId" value="">
                <input type="hidden" name="companyName" id="companyName" value="">
                  
                <div id="servio_price_info" class="text-center">
                   
                </div>
                  
                 <button type="button" id="add_servio_reserve" class="mt-2 ui-btn ui-btn-danger-dark ui-btn-icon-task ui-btn-round">
                    Reserve!
                 </button>
            </form>`


        if(servioBtn !== null)
        {
            //формируем popup с формой
            popupObj = self.makePopupV2('servio-hotel-reservation',html,'Hotel Reservation',popupBtnevents)

            this.makeAjaxRequest(this.url.ajax,{'ACTION' : 'GET_COMPANY_INFO'},
                function (response) {
                    console.log('COMPANY RESULT',response);

                    if(response.error != false)
                    {
                        self.addErrorsBeforeForm(response.error,'error')
                    }
                    else
                    {
                        company.id = response.result.CompanyID
                        company.name = response.result.CompanyName


                        let companyIdField = document.getElementById('companyId'),
                            companyNameField = document.getElementById('companyName')
                        if(companyIdField !== null)
                        {
                            companyIdField.value = response.result.CompanyID;
                        }
                        if(companyNameField !== null)
                        {
                            companyNameField.value = response.result.CompanyName;
                        }
                    }


                })


            servioBtn.onclick = () => {

                //здесь реакция на изменение полей

                //  1,2  Изменение поля dateFrom и dateTo
                let dateFromInput = document.getElementById('dateFrom'),
                    dateToInput = document.getElementById('dateTo')

                if(dateFromInput !== null && dateToInput !== null)
                {
                    dateFromInput.onchange = () => {

                        let startDate = new Date(dateFromInput.value),
                            finishDate = new Date(dateToInput.value)

                        if(startDate >= finishDate)
                        {
                            finishDate.setDate(startDate.getDate() + 1)
                            dateToInput.value = self.createDate(finishDate)
                        }

                        //обновление цен  +  селекта комнат
                        self.getRoomsByFilterNew()
                        // self.showReserveButton();
                    }

                    dateToInput.onchange = () => {
                        let startDate = new Date(dateFromInput.value),
                            finishDate = new Date(dateToInput.value)
                        if(startDate >= finishDate)
                        {
                            startDate.setDate(finishDate.getDate() - 1)
                            dateFromInput.value = self.createDate(startDate)
                        }

                        //обновление цен  +  селекта комнат
                        self.getRoomsByFilterNew()
                        // self.showReserveButton();
                    }
                }




                //4. Изменение Селекта
                let roomsCategorySelect = document.getElementById('roomCategory')
                if(roomsCategorySelect !== null)
                {
                    roomsCategorySelect.onchange =  function () {
                        self.showReserveButton();
                    }
                }


                // 6,7 Изменение значений в полях Adults и Childs
                let adultsAndChildFields = document.querySelectorAll('#adults, #childs')
                if(adultsAndChildFields.length > 0)
                {
                    // let roomCatField = document.getElementById('roomCategory')
                    for(let elem of adultsAndChildFields)
                    {
                        //запрос категорий при изменении значений полей
                        elem.onchange = function () {
                            self.getRoomsByFilterNew()
                            // self.showReserveButton();
                        }

                        //удаление из полей всего кроме цифр
                        elem.onkeyup = function () {
                            this.value = Number(this.value.replace(/[^\d]/g,''))
                        }
                    }
                }


                // 8. Изменение селекта  типа оплат
                let paidTypeField = document.getElementById('paidType')
                if(paidTypeField !== null)
                {
                    paidTypeField.onchange = () => {
                        self.showReserveButton();
                    }
                }


                // 9. Резервирование
                let reserveButton = document.getElementById('add_servio_reserve')
                if(reserveButton !== null)
                {
                    reserveButton.onclick = () => {
                        self.addReservation(popupObj)
                    }
                }

                popupObj.show()
            }
        }

    }



    loadServioReservePopup()
    {
        // console.log(this.deal);

        let self= this,
            html = `<div id="servio_reserve_view"></div>`,
            servioBtn = document.getElementById('servio'),
            popupObj = {},
            popupBtnevents =
                {
                    onPopupClose: function(PopupWindow) {
                        // Событие при закрытии окна
                        PopupWindow.destroy()
                    },

                    // События при показе окна
                    onPopupShow: function() {

                    },
                }

        if(servioBtn !== null)
        {
            servioBtn.onclick = () =>
            {

                //получаем данные резерва
                this.makeAjaxRequest(this.url.ajax, {'ACTION': 'GET_RESERVE_BY_ID', 'RESERVE_ID': this.deal.reserveId},
                    function (response) {
                        console.log('RESERVE DATA', response)

                        popupObj = self.makePopupV2('servio-hotel-reservation-view',html,'Hotel Reservation View',popupBtnevents)
                        let popupBody = document.getElementById('servio_reserve_view')


                        //error append
                        if(!response.Result === 0)
                        {
                            if(popupBody !== null)
                            {
                                popupBody.innerHTML =
                                    `
                                        <div class="ui-alert ui-alert-danger custom-error">
                                            <span class="ui-alert-message"><strong>Error! </strong>${response.Error}</span>
                                        </div>
                                    `
                            }
                        }
                        else
                        {
                            let servisesTable = ''

                            // response.Services.forEach(([key, service]) => {
                            for(let service of response.Services){
                                // console.log('1',service);
                                for(let price of service.PriceDate){
                                    servisesTable +=
                                        `
                                            <tr>
                                                <td>${service.ServiceName}</td>
                                                <td>${price.Date}</td>
                                                <td>${price.Price}</td>
                                                <td class="ui-alert ${(price.IsPaid === true) ? 'ui-alert-success' : 'ui-alert-danger'}">${price.IsPaid}</td>
                                            </tr>
                                        `
                                }
                            }

                            let reserveData =
                                `
                                    <div class="row ">
                                        <div class="col-sm-6 ui-alert ui-alert-default">Serviceprovider Name</div>
                                        <div class="col-sm-6 ui-alert ui-alert-success">
                                            <span class="ui-alert-message"><strong>${response.ServiceProviderName}</strong></span>
                                            </div>
                                    </div>
                                    <div class="row ">
                                        <div class="col-sm-6 ui-alert ui-alert-default">Reserve Status</div>
                                        <div class="col-sm-6 ui-alert ui-alert-danger">
                                            <span class="ui-alert-message"><strong>${response.StatusName}</strong></span>
                                            </div>
                                    </div>
                                    <div class="row ">
                                        <div class="col-sm-6 ui-alert ui-alert-default">Account Name</div>
                                        <div class="col-sm-6 ui-alert ui-alert-primary">${response.AccountName}</div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-6 ui-alert ui-alert-default">Email</div>
                                        <div class="col-sm-6 ui-alert ui-alert-primary">${response.Email}</div>
                                    </div>
                                      <div class="row">
                                        <div class="col-sm-6 ui-alert ui-alert-default">Date From</div>
                                        <div class="col-sm-6 ui-alert ui-alert-primary">${response.DateArrival}</div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-6 ui-alert ui-alert-default">Date To</div>
                                        <div class="col-sm-6 ui-alert ui-alert-primary">${response.DateDeparture}</div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-6 ui-alert ui-alert-default">Adults</div>
                                        <div class="col-sm-6 ui-alert ui-alert-primary">${response.Adults}</div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-6 ui-alert ui-alert-default">Childs</div>
                                        <div class="col-sm-6 ui-alert ui-alert-primary">${response.Childs}</div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-6 ui-alert ui-alert-default">Room Type</div>
                                        <div class="col-sm-6 ui-alert ui-alert-primary">${response.RoomType}</div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-6 ui-alert ui-alert-default">Paid Type</div>
                                        <div class="col-sm-6 ui-alert ui-alert-primary">${response.PaidType}</div>
                                    </div>
                                    
                                   <div class="row">
                                        <table class="table table-sm table-responsive">
                                            <thead>
                                                <th>Servise</th>
                                                <th>Dates</th>
                                                <th>Prices, ${response.ValuteShort}</th>
                                                <th>Is Paid</th>
                                            </thead>
                                            <tbody>
                                                ${servisesTable}
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <button class="ui-btn ui-btn-danger" id="reserveCancelBtn">Отменить</button>
                                    <button class="ui-btn ui-btn-success" id="reserveAcceptBtn">Подтвердить</button>
                            `
                            popupBody.innerHTML = reserveData

                        }


                        //НАЖАТИЕ НА КНОПКИ
                        let cancelBtn = document.getElementById('reserveCancelBtn'),
                            acceptBtn = document.getElementById('reserveAcceptBtn')

                        if(cancelBtn !== null)
                        {
                            cancelBtn.onclick = () => {
                                console.log('cancelBtn');
                            }
                        }

                        if(acceptBtn !== null)
                        {
                            acceptBtn.onclick = () => {
                                console.log('acceptBtn');
                            }
                        }

                        //show popup
                        popupObj.show()
                    })
            }
        }

    }




}





