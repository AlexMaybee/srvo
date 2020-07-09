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
            reserveConfirmFileId: '',
            reserveConfirmFile: ''
        }
        this.categoriesObj = {} //для хранения данных категорий для дополнения селектовпри выборе


        /*
        * По шагам
        * */

        // 0.1 Получение Id сделки (для получения отве., контакта, записи в поля)
        // this.getDealIdAndReserveId()

        let matchMassive, result = false, self = this,
            servioBtn = document.getElementById('servio')


        matchMassive = window.location.href.match(/\/crm\/deal\/details\/([\d]+)/i)

        if(matchMassive && servioBtn !== null)
        {
            this.deal.id = Number(matchMassive[1])
            // console.log('Search button',servioBtn,this.deal);
            if(this.deal.id > 0)
            {

                servioBtn.onclick = function () {
                    self.makeAjaxRequest(self.url.ajax,
                        {
                            'ACTION' : 'GET_DEAL_SERVIO_FIELDS',
                            'DEAL_ID' : self.deal.id
                        },
                        function (response) {
                            self.deal.reserveId = response.UF_CRM_HMS_RESERVE_ID || 0;
                            self.deal.reserveConfirmFileId = response.UF_CRM_HMS_RESERVE_CONFIRM_FILE_ID;
                            self.deal.reserveConfirmFile = response.UF_CRM_HMS_RESERVE_CONFIRM_FILE;

                            if(self.deal.id > 0 && self.deal.reserveId === 0)
                            {
                                //здесь popup с формой

                                self.loadReservePopupV5()
                            }
                            else if(self.deal.id > 0 && self.deal.reserveId > 0)
                            {
                                //здесь popup с полученным по id данными резерва

                                console.log('NEW POPUP WITH RESERVE DATA');

                                // self.loadServioReservePopup()
                                self.loadServioPopupWithReserervation()
                            }
                            else
                            {
                                //иначе форма без возможности резерва
                                // self.loadServioPopup();

                                self.loadReservePopupV5()
                                console.log('ШТА?');
                            }

                        })
                }

                //поиск кнопки + нажатие + проверка ШВ резерва или отображение формы

            }

            //если ID сделки == 0, то  в попапе сделать бронь нельзя
            else
            {
                // console.log('iiooo',this.deal);
                self.loadReservePopupV5()
            }
        }

    }


    /*
     * Universal ajax request
      *  */
    makeAjaxRequest(urlS,dataS,closeFunction = false)
    {
        if(!closeFunction)
        {
            closeFunction = function () {}
        }

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


    makePopupV2(popupTechName,htmlContent,popupTitle)
    {
        let PopupProductProvider = BX.PopupWindowManager.create(popupTechName, BX('element'), {
            content: htmlContent,
            width: 700, // ширина окна
            zIndex: 100, // z-index
            closeIcon: { // объект со стилями для иконки закрытия, при null - иконки не будет
                opacity: 1,
                backgroundColor: '#000'
            },
            offsetTop: 0,
            titleBar: popupTitle,
            closeByEsc: true, // закрытие окна по esc
            darkMode: false, // окно будет светлым или темным
            autoHide: true, // закрытие при клике вне окна
            draggable: true, // можно двигать или нет
            resizable: true, // можно ресайзить
            min_height: 100, // минимальная высота окна
            min_width: 100, // минимальная ширина окна
            lightShadow: true, // использовать светлую тень у окна
            // angle: true, // появится уголок
            overlay: {
                // объект со стилями фона
                backgroundColor: 'black',
                opacity: 500
            },
            // events: myBtnevents,
            events: {
                onPopupClose: function(PopupWindow) {
                    // Событие при закрытии окна
                    PopupWindow.destroy()
                    console.log('Window might be destroyed');
                },
                // onPopupShow: function() {
                //     // Событие при показе окна
                //     $('#add_servio_reserve').click(function () {
                //         console.log('modal show test!');
                //
                //     });
                // },
            }
        })


        // // if(!myBtnevents)
        // // {
        // myBtnevents = {
        //     onPopupClose: function (PopupWindow) {
        //         // Событие при закрытии окна
        //         PopupWindow.destroy()
        //     },
        // }
        // // }

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
                                <span class="ui-alert-message"><strong>Success!</strong> ${errText}</span>
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
    // getRoomsByFilterNew()
    // {
    //     let self = this,
    //         popup = document.getElementById('servio-hotel-reservation'),
    //         form = document.getElementById('servio_popup'),
    //         roomCategorySelect = document.getElementById('roomCategory'),
    //         priceTableBlock = document.getElementById('servio_price_info'),
    //         fields = {},  //данные формы
    //         data = {'ACTION' : 'GET_CATEGORIES_WITH_ROOMS',  'FIELDS': {}},
    //         roomOptions = '<option value="">Select...</option>',
    //         tableBody = '',
    //         priceTable = '',
    //         i = 1
    //
    //     //удаление ошибоки др. уведомлений
    //     this.deleteErrorsinForm(popup)
    //
    //
    //     //данные из формы
    //     fields = this.getFromFieldsData(form)
    //
    //     if(Object.keys(fields).length <= 0)
    //     {
    //
    //         console.log('Error! Проблема с получением данных формы')
    //         //скрываем/отображаем кнопку резерва
    //         this.showReserveButton();
    //     }
    //     else
    //     {
    //         data.FIELDS = fields
    //
    //         this.makeAjaxRequest(this.url.ajax,data,
    //             function (response) {
    //                 console.log('ROOMS NEW', response);
    //
    //                 if(response.error !== false)
    //                 {
    //                     self.addErrorsBeforeForm(response.error,'error')
    //
    //
    //                     //очищение селекта комнат + списка цен
    //                     if(roomCategorySelect !== null)
    //                     {
    //                         roomCategorySelect.innerHTML = roomOptions
    //                     }
    //
    //                     if(priceTableBlock !== null)
    //                     {
    //                         priceTableBlock.innerHTML = ''
    //                     }
    //
    //                 }
    //                 else
    //                 {
    //                     if(response.result.rooms == null)
    //                     {
    //                         //на всякий случай
    //                         console.log('Что-то с результатом списка комнат, его нет!');
    //                         self.categoriesObj = {}
    //                     }
    //                     else
    //                     {
    //
    //                         //сохраняем массив категорий с ценами
    //                         self.categoriesObj = response.result.rooms
    //
    //                         Object.entries(response.result.rooms).forEach(([key, row]) => {
    //                             roomOptions += `<option value="${row.roomTypeId}">${row.roomTypeName} (${row.FreeRoom}  ${(row.FreeRoom > 1)  ? 'rooms' : 'room' } )</option>`
    //
    //                             tableBody +=
    //                                 `<tr>
    //                                        <td>${i}</td>
    //                                        <td>${row['roomTypeName']}</td>
    //                                        <td>${row['dates']}</td>
    //                                        <td>${row.totalDays}</td>
    //                                        <td>${row.totalPrice}, ${row.currency}</td>
    //                                    </tr>`
    //                             i++
    //                         })
    //
    //                         // console.log('Option',roomOptions);
    //
    //                         priceTable =
    //                             `<table class="table table-sm table-responsive">
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
    //                         if(roomCategorySelect !== null)
    //                         {
    //                             roomCategorySelect.innerHTML = roomOptions
    //                         }
    //
    //                         if(priceTableBlock !== null)
    //                         {
    //                             priceTableBlock.innerHTML = priceTable
    //                         }
    //
    //                     }
    //                 }
    //
    //                 self.showReserveButton();
    //
    //             })
    //
    //
    //     }
    //
    //     // console.log('Получаем данные формы для обновления данных по номерам',data );
    // }

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

    // showReserveButton()
    // {
    //
    //     // console.log('CATEGORIES ARR',this.categoriesObj);
    //
    //     let reserveButton = document.getElementById('add_servio_reserve'),
    //         formData = {}
    //
    //
    //     formData = this.getFromFieldsData(document.getElementById('servio_popup'))
    //
    //     // console.log('deal id: ',this.deal);
    //     // console.log('reserveButton: ',reserveButton);
    //     // console.log('fields: ',formData);
    //
    //     if(reserveButton !== null && Object.keys(formData).length > 0)
    //     {
    //         //резерв не позволителет если id сделки === 0 (сделка не существует еще)
    //         if(
    //             this.deal.id > 0
    //             &&
    //             Number(formData.roomCategory) > 0
    //             &&
    //             formData.dateFrom != ''
    //             &&
    //             formData.dateTo != ''
    //             &&
    //             formData.adults !== ''
    //             &&
    //             formData.childs !==  ''
    //             &&
    //             formData.paidType !==  ''
    //         )
    //         {
    //             reserveButton.style.display = 'inline-flex';
    //         }
    //         else
    //         {
    //             reserveButton.style.display = 'none';
    //         }
    //     }
    //     else
    //     {
    //         reserveButton.style.display = 'none';
    //     }
    // }

    //создание резерва
    addReservation(popupObj,buttonObj)
    {
        let self = this,
            formData = {},
            popup = document.getElementById('servio_popup'),
            reserveButtons

        reserveButtons = popup.querySelectorAll('.add-reserve')
        // console.log('3123',reserveButtons);

        // console.log('reserve test',buttonObj);


        // this.disableElement(buttonObj)
        this.toggleClockLoaderToBtn(buttonObj)

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

                        // console.log('Reserve Response',response)


                        if(reserveButtons.length > 0)
                        {
                            reserveButtons.forEach(btn => {
                                self.disableElement(btn)
                            })
                        }

                        if(response.error !== false)
                        {
                            self.addErrorsBeforeForm(response.error,'error')
                        }
                        else
                        {
                            let form = document.getElementById('servio_popup')
                            if(form !== null)
                            {
                                self.addErrorsBeforeForm(`Создана бронь № ${response.result}`,'success');

                                // if(reserveButtons.length > 0)
                                // {
                                //     reserveButtons.forEach(btn => {
                                //         self.disableElement(btn)
                                //     })
                                // }

                                setTimeout(()=>{
                                    popupObj.destroy()

                                    self.deal.reserveId = response.result

                                    console.log('Reservr ID Result',self.deal);

                                    // self.loadServioReservePopup()
                                    self.loadServioPopupWithReserervation()

                                    let servioBtn = document.getElementById('servio')
                                    servioBtn.click();
                                },2000)
                            }
                        }

                        self.toggleClockLoaderToBtn(buttonObj)
                    })
            }
            else
            {
                //some custom error
                console.log('NO more RESERVATION function, Error!');
            }

        }
        else
        {
            console.log('Reserve will not create!!!');
        }

    }


    loadReservePopupV5()
    {
        let self = this,
            dateStart = new Date(),
            dateFinish = new Date(),
            dateFrom = '',
            dateTo = '',
            popupObj = {},
            html = ``,
            company = {
                id : 0,
                name : ''
            },
            form,
            searchBtn,
            priceHtmlBlock = '',
            i,
            priceTableTh = '',
            priceTableBody = '',
            reserveButtons,
            roomCategoryInput

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
                       <!--<option value="">Select...</option>-->
                       <option value="100" selected>Cash</option>
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
               <input type="hidden" name="roomCategory" id="roomCategory" value="">
                
               <div class="ui-btn-container ui-btn-container-center text-right">
                  <button type="button" id="servio_search" class="mt-2 ui-btn ui-btn-primary-dark ui-btn-right ui-btn-icon-search">
                      Search
                  </button>
               </div>
                
                <div class="form-row reserve-hidden hidden-input">
                   <div class="form-group col-sm">
                       <label for="address">Address</label>
                       <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                   </div>
               </div>
               
                <div class="form-row reserve-hidden hidden-input">
                   <div class="form-group col-sm">
                       <label for="comment">Comment</label>
                       <textarea class="form-control" id="comment" name="comment" rows="3"></textarea>
                   </div>
               </div>
                  
               <div id="servio_price_info" class="text-center">
                   
               </div>
                  
               <button type="button" id="add_servio_reserve" class="mt-2 ui-btn ui-btn-danger-dark ui-btn-icon-cloud">
                  Reserve!
               </button>
            </form>`

        popupObj = self.makePopupV2('servio-hotel-reservation', html, 'Hotel Reservation')
        form = document.getElementById('servio_popup')
        roomCategoryInput = document.getElementById('roomCategory')

        //заполнение полей компании в форме
        this.makeAjaxRequest(this.url.ajax,{'ACTION' : 'GET_COMPANY_INFO'},
            function (response) {
                console.log('COMPANY RESULT', response);

                if (response.error != false) {
                    // self.addErrorsBeforeForm(response.error, 'error')
                    self.addErrorsBeforeFormNew(form, response.error, 'error')
                }
                else {
                    company.id = response.result.CompanyID
                    company.name = response.result.CompanyName


                    let companyIdField = document.getElementById('companyId'),
                        companyNameField = document.getElementById('companyName'),
                        companyCodeIdField = document.getElementById('companyCodeId')

                    if (companyIdField !== null) {
                        companyIdField.value = response.result.CompanyID;
                    }
                    if (companyNameField !== null) {
                        companyNameField.value = response.result.CompanyName;
                    }
                    if (companyCodeIdField !== null) {
                        companyCodeIdField.value = response.result.CompanyCodeID;
                    }
                }
            }
        )


        //нажатие на поиск
        searchBtn = document.getElementById('servio_search')
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

                    self.toggleClockLoaderToBtn(searchBtn)

                    //ajax для получения цен

                    self.makeAjaxRequest(self.url.ajax,{ACTION : 'GET_PRICES_BY_FILTER',FIELDS : fields},
                        function (response) {

                            i = 1
                            priceHtmlBlock = document.getElementById('servio_price_info')


                            console.log('GET PRICES LAST',response);

                            if(Object.keys(response.table).length > 0)
                            {
                                self.categoriesObj = response.table

                                priceTableBody = ''
                                Object.entries(response.table).forEach(([key, row]) => {
                                    priceTableBody +=
                                        `<tr>
                                            <td>${i}</td>
                                            <td>${row.CategoryName}</td>
                                            <td>${row.FreeRoom}</td>
                                            <td>${row.Date}</td>
                                            <td>${row.MinPayDays}</td>
                                            <td>${row.MinStayDays}</td>
                                            <td>${row.NearestDateToReservation}</td>
                                            <td>${row.Price.toFixed(2)} ${row.Currency}</td>
                                            ${
                                            self.deal.id > 0
                                                ? '<td><button class="ui-btn ui-btn-xs ui-btn-primary-dark add-reserve ' + ((row.FreeRoom == 0) ? 'servio-custom-disable' : '')  +  '" data-category-id="' + row.ID + '">Reserve</button></td>'
                                                : '<td></td>'
                                            }
                                        </tr>`

                                    i++
                                })

                                priceTableTh =
                                    `<table class="table table-sm table-responsive text-center">
                                                <thead>
                                                   <tr>
                                                       <th scope="col-sm">#</th>
                                                       <th scope="col-sm">Type</th>
                                                       <th scope="col-sm">Free Room</th>
                                                       <th scope="col-sm">Date</th>
                                                       <th scope="col-sm">Min Pay Days</th>
                                                       <th scope="col-sm">Min Days</th>
                                                       <th scope="col-sm">Neares Date To Free</th>
                                                       <th scope="col-sm">Total Price</th>
                                                       <th scope="col-sm"></th>
                                                   </tr>
                                                </thead>
                                                <tbody> ${priceTableBody}</tbody>
                                            </table>`

                                priceHtmlBlock.innerHTML = priceTableTh;

                                //show/hide 2 fields
                                self.toggleReserveFields(true)

                                reserveButtons = document.querySelectorAll('.add-reserve')
                                // console.log('Reserve Buttons',reserveButtons);

                                if(reserveButtons.length > 0)
                                {
                                    reserveButtons.forEach(reserveButton => {
                                        reserveButton.onclick = function(){
                                            //Вставляем в поле ID выбранной категории комнат
                                            roomCategoryInput.value = this.dataset.categoryId
                                            self.addReservation(popupObj,this)

                                            // console.log('Button Click!!!!!')
                                        }
                                    })
                                }

                            }
                            else
                            {
                                self.categoriesObj = {}
                                roomCategoryInput.value = ''
                                priceHtmlBlock.innerHTML = '';

                                //show/hide 2 fields
                                self.toggleReserveFields(false)

                            }

                            self.toggleClockLoaderToBtn(searchBtn)

                        }
                    )
                }



            }
        }


        //  1,2  Изменение поля dateFrom и dateTo
        let dateFromInput = document.getElementById('dateFrom'),
            dateToInput = document.getElementById('dateTo')

        if(dateFromInput !== null && dateToInput !== null)
        {
            dateFromInput.onchange = () => {

                let startDate = new Date(dateFromInput.value),
                    finishDate = new Date(dateToInput.value)

                console.log('DDDD',startDate,finishDate,startDate > finishDate);

                if(startDate >= finishDate)
                {
                    if(startDate.getMonth() === finishDate.getMonth())
                    {
                        finishDate.setDate(startDate.getDate() + 1)
                    }
                    else if(startDate.getMonth() > finishDate.getMonth())
                    {
                        finishDate.setDate(startDate.getDate() + 1)
                        finishDate.setMonth(startDate.getMonth())
                    }
                    else if(startDate.getFullYear() > finishDate.getFullYear())
                    {
                        finishDate.setDate(startDate.getDate() + 1)
                        finishDate.setMonth(startDate.getMonth())
                        finishDate.setFullYear(startDate.getFullYear())
                    }
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
                    // startDate.setDate(finishDate.getDate() - 1)

                    if(startDate.getMonth() === finishDate.getMonth())
                    {
                        startDate.setDate(finishDate.getDate() - 1)
                    }
                    if(startDate.getMonth() > finishDate.getMonth())
                    {
                        startDate.setDate(finishDate.getDate() - 1)
                        startDate.setMonth(finishDate.getMonth())
                    }
                    if(startDate.getFullYear() > finishDate.getFullYear())
                    {
                        startDate.setDate(finishDate.getDate() - 1)
                        startDate.setMonth(finishDate.getMonth())
                        startDate.setFullYear(finishDate.getFullYear())
                    }
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
            for(let elem of adultsAndChildFields)
            {
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
        popupObj.show()
    }

    loadServioPopupWithReserervation()
    {
        let self= this,
            servioBtn = document.getElementById('servio'),
            popupObj = {},
            html = `<div id="servio_reserve_view"></div>`,
            popupBody,
            servisesTable = '',
            servisesTableInner = '',
            reserveDataHtml = '',
            cancelBtn, confirmBtn, billBtn


        popupObj = self.makePopupV2('servio-hotel-reservation-view',html,'Hotel Reservation View',)

        //получаем данные резерва
        this.makeAjaxRequest(this.url.ajax, {'ACTION': 'GET_RESERVE_BY_ID', 'RESERVE_ID': this.deal.reserveId},
            function (response) {

                console.log('Load Reserve',response);
                popupBody = document.getElementById('servio_reserve_view')

                if(response.Result !== 0)
                {
                    if(popupBody !== null)
                    {
                        reserveDataHtml =
                            `<div class="ui-alert ui-alert-danger custom-error">
                                <span class="ui-alert-message"><strong>Error! </strong>${response.Error}</span>
                            </div>`
                    }
                }
                else
                {
                    if(response.Services.length > 0)
                    {
                        for(let service of response.Services){
                            for(let price of service.PriceDate){
                                servisesTableInner +=
                                    `<tr>
                                        <td>${service.ServiceName}</td>
                                        <td>${price.Date}</td>
                                        <td>${price.Price}</td>
                                        <td class="ui-alert ${(price.IsPaid === true) ? 'ui-alert-success' : 'ui-alert-danger'}">${price.IsPaid}</td>
                                    </tr>`
                            }
                        }

                        servisesTable =
                            `<div class="row">
                                <table class="table table-sm table-responsive">
                                    <thead>
                                        <th>Servise</th>
                                        <th>Dates</th>
                                        <th>Prices, ${response.ValuteShort}</th>
                                        <th>Is Paid</th>
                                    </thead>
                                    <tbody>
                                        ${servisesTableInner}
                                    </tbody>
                                </table>
                            </div>`
                    }


                    reserveDataHtml =
                        `<div class="row">
                                        <div class="col-sm-6 ui-alert ui-alert-default">Serviceprovider Name</div>
                                        <div class="col-sm-6 ui-alert ui-alert-primary">
                                            <span class="ui-alert-message"><strong>${response.ServiceProviderName}</strong></span>
                                            </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-6 ui-alert ui-alert-default">Reserve Status</div>
                                        <div class="col-sm-6 ui-alert ui-alert-danger">
                                            <span class="ui-alert-message"><strong>${response.StatusName}</strong></span>
                                            </div>
                                    </div>
                                    <div class="row">
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
                                        <div class="col-sm-6 ui-alert ui-alert-primary">${response.PaidTypeText}</div>
                                    </div>
                                    
                                    ${servisesTable}
                                    
                                    
                                    ${
                            (
                                Number(self.deal.reserveId) > 0
                            )
                                ? '<button class="ui-btn ui-btn-danger" id="reserveCancelBtn">Отменить</button>'
                                : ''

                            }
                                    
                                    ${
                            (
                                Number(self.deal.reserveId) > 0 &&
                                (self.deal.reserveConfirmFileId > 0) != true &&
                                self.deal.reserveId != null
                            )
                                ? '<button class="ui-btn ui-btn-success" id="reserveAcceptBtn">Подтвердить</button>'
                                : ''
                            }
                                    
                                    <button class="ui-btn ui-btn-secondary" id="getReserveBill">Счет</button>`
                }


                console.log('OOOOOO',self.deal);
                popupBody.innerHTML = reserveDataHtml


                //НАЖАТИЕ НА КНОПКИ
                cancelBtn = document.getElementById('reserveCancelBtn')
                confirmBtn = document.getElementById('reserveAcceptBtn')
                billBtn = document.getElementById('getReserveBill')

                // console.log(cancelBtn,confirmBtn);

                if(cancelBtn !== null)
                {
                    cancelBtn.onclick = () =>
                    {
                        console.log('cancelBtn');
                    }
                }

                if(confirmBtn !== null)
                {
                    confirmBtn.onclick = () =>
                    {
                        // console.log('confirmBtn');
                        self.confirmReserve(confirmBtn)
                        // self.testGetDocument(28199)
                    }
                }

                if(billBtn !== null)
                {
                    billBtn.onclick = () =>
                    {
                        console.log('billBtn');
                        self.getBillForReserve(billBtn)
                    }
                }

            }
        )

        //show popup
        popupObj.show()
    }

    disableElement(elemObj)
    {
        if(!elemObj.classList.contains('servio-custom-disable'))
        {
            elemObj.classList.add('servio-custom-disable')
        }
        // else
        // {
        //     elemObj.classList.remove('servio-custom-disable')
        // }
    }

    toggleClockLoaderToBtn(btnObj)
    {
        if(!btnObj.classList.contains('ui-btn-clock'))
        {
            btnObj.classList.add('ui-btn-clock')
            btnObj.classList.add('servio-tmp-disable')
        }
        else
        {
            btnObj.classList.remove('ui-btn-clock')
            btnObj.classList.remove('servio-tmp-disable')
        }
    }

    //отображение/скрытие 2х полей
    toggleReserveFields(flag)
    {
        let form = document.getElementById('servio_popup'),
            hiddenBlocks = document.querySelectorAll('.reserve-hidden')

        if(hiddenBlocks.length > 0 )
        {
            hiddenBlocks.forEach(elem => {
                // console.log(222,elem);
                if(flag === true /*&& elem.classList.contains('hidden-input')*/)
                {
                    elem.classList.remove('hidden-input')
                }
                else
                {
                    elem.classList.add('hidden-input')
                }
            })
        }
        // console.log('UUUU',hiddenBlocks);

    }

    // testGetDocument(docId)
    // {
    //     this.makeAjaxRequest(this.url.ajax, {'ACTION': 'TEST_GET_DOCUMENT', 'DOCUMENT_ID': docId},
    //         function (response) {
    //             console.log('TEST_GET_DOCUMENT', response)
    //         }
    //     )
    // }

    confirmReserve(btnObj)
    {
        let self = this,
            viewPopup = document.getElementById('servio_reserve_view'),
            firstRow = viewPopup.querySelector('.row')

        this.deleteErrorsinForm(viewPopup)

        this.toggleClockLoaderToBtn(btnObj)

        this.makeAjaxRequest(this.url.ajax, {'ACTION': 'CONFIRM_RESERVE', 'FIELDS': self.deal},
            function (response) {
                console.log('CONFIRM RESERVE', response)

                if(response.error)
                {
                    self.addErrorsBeforeFormNew(firstRow,response.error,'error')
                }
                else
                {
                    self.addErrorsBeforeFormNew(firstRow,'Файл потверждения сохранен в сделке! Обновите страницу!','success')
                }

                self.disableElement(btnObj)
                self.toggleClockLoaderToBtn(btnObj)
            }
        )
    }

    getBillForReserve(btnObj)
    {
        let self = this,
            viewPopup = document.getElementById('servio_reserve_view'),
            firstRow = viewPopup.querySelector('.row')

        this.deleteErrorsinForm(viewPopup)

        this.toggleClockLoaderToBtn(btnObj)

        // ajax to get bill
        this.makeAjaxRequest(this.url.ajax, {'ACTION': 'GET_BILL_FOR_RESERVE', 'FIELDS': self.deal},
            function (response) {
                console.log('RESERVE BILL DATA', response)

                if(response.error)
                {
                    self.addErrorsBeforeFormNew(firstRow,response.error,'error')
                }
                else
                {
                    self.addErrorsBeforeFormNew(firstRow,'Файл о счетом ПОКА ЕЩЕ НЕ сохранен в сделке! Обновите страницу!','success')
                }

                self.toggleClockLoaderToBtn(btnObj)
            }
        )

    }



}