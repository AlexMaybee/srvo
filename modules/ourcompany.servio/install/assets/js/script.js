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
                    self.decideWichWindow(servioBtn);
                }
            }

            //если ID сделки == 0, то  в попапе сделать бронь нельзя
            else
            {
                // console.log('iiooo',this.deal);
                self.loadFormV6()
            }
        }

    }

    /*
    * Main function to get deal Fields and choose popup to show
    * */
    decideWichWindow(servioBtn)
    {
        let self = this

        console.log('START FROM LOADing DEAL FIELDS',this.deal);

        BX.ajax.runAction('ourcompany:servio.nmspc.handler.dealFields', {
            data: {
                DEAL_ID: this.deal.id, //в php принимаемый параметр должен иметь то же имя
            }
        })
            .then(
                (ajaxResult) => {

                    console.log('Result Promice DEAL',ajaxResult);

                    // повторяем то, что уже есть выше
                    self.deal.reserveId = ajaxResult.data.UF_CRM_HMS_RESERVE_ID || 0;
                    self.deal.reserveConfirmFileId = ajaxResult.data.UF_CRM_HMS_RESERVE_CONFIRM_FILE_ID;
                    self.deal.reserveConfirmFile = ajaxResult.data.UF_CRM_HMS_RESERVE_CONFIRM_FILE;

                    if(self.deal.id > 0 && self.deal.reserveId === 0)
                    {
                        //здесь popup с формой
                        self.loadFormV6()
                    }
                    else if(self.deal.id > 0 && self.deal.reserveId > 0)
                    {
                        //здесь popup с полученным по id данными резерва
                        // self.loadServioPopupWithReserervation()
                        self.loadServioPopupWithReserervationV4()
                    }
                    else
                    {
                        //иначе форма без возможности резерва
                        self.loadFormV6()
                        console.log('ШТА?');

                        servioBtn.classList.remove('ui-btn-danger')
                        servioBtn.classList.add('ui-btn-success')
                    }
                })
            .catch(
                (ajaxResult) => {
                    console.log('Errors: ', ajaxResult.errors);

                    let title = ''
                    for(let err of ajaxResult.errors)
                    {
                        title += err.message
                    }
                    console.log('EEEE',title);
                    servioBtn.classList.remove('ui-btn-success')
                    servioBtn.classList.add('ui-btn-danger')
                    servioBtn.title = title
                }
            );

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
    // addErrorsBeforeForm(errText,flag)
    // {
    //     let form = document.getElementById('servio_popup')
    //
    //     if(form !== null)
    //     {
    //         if(flag === 'error')
    //         {
    //             $(form).before(`<div class="ui-alert ui-alert-danger custom-error">
    //                             <span class="ui-alert-message"><strong>Error!</strong> ${errText}</span>
    //                         </div>`);
    //         }
    //         else
    //         {
    //             $(form).before(`<div class="ui-alert ui-alert-success custom-success">
    //                             <span class="ui-alert-message"><strong>Error!</strong> ${errText}</span>
    //                         </div>`);
    //         }
    //     }
    // }

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

    addReservationNew(popupObj,reserveButtons,buttonObj,formData,form)
    {
        let self = this,
            servioBtn = document.getElementById('servio')

        this.toggleClockLoaderToBtn(buttonObj)

        //страховка
        if(
            this.deal.id > 0
            &&
            formData.roomCategory.trim() != ''
            && this.categoriesObj.hasOwnProperty(formData.roomCategory)
        )
        {
            //получаем contract conditions + paid type
            BX.ajax.runAction('ourcompany:servio.nmspc.handler.addReserve', {
                data: {
                    FIELDS : {
                        FILTERS: formData,
                        ROOM_CATEGORY : self.categoriesObj[formData.roomCategory],
                        DEAL_ID : self.deal.id,
                    }
                }
            })
                .then(
                    (reserveAjaxObj) =>
                    {
                        console.log('SUCC Reserve obj',reserveAjaxObj);

                        if(reserveAjaxObj.data.errors.length > 0)
                        {
                            for(let rErr of reserveAjaxObj.data.errors)
                            {
                                self.addErrorsBeforeFormNew(form, rErr, 'error')
                            }
                        }
                        else
                        {
                            let form = document.getElementById('servio_popup')
                            if(form !== null)
                            {
                                self.addErrorsBeforeFormNew(form,`Создана бронь № ${reserveAjaxObj.data.result}`,'success');

                                setTimeout(()=>{
                                    popupObj.destroy()
                                    self.deal.reserveId = reserveAjaxObj.data.result
                                    self.decideWichWindow(servioBtn);
                                },2000)
                            }
                        }
                    }
                )
                .catch(
                    (reserveAjaxObj)=>
                    {
                        console.log('ERR Reserve obj',reserveAjaxObj)

                        for(let arErr of reserveAjaxObj.errors)
                        {
                            self.addErrorsBeforeFormNew(form, arErr.message, 'error')
                        }
                    }
                )

            if(reserveButtons.length > 0)
            {
                reserveButtons.forEach(btn => {
                    self.disableElement(btn)
                })
            }

            this.toggleClockLoaderToBtn(buttonObj)
        }
        else
        {
            console.log('EEE Reserve will not create! Or Deal Not exists, or error with params!');
        }

    }


    //замена для V5
    loadFormV6()
    {
        let self = this,
            dateStart = new Date(),
            dateFinish = new Date(),
            dateFrom = '',
            dateTo = '',
            popupObj = {},
            html = ``,
            form,
            nextStepBtn,
            searchBtn,
            step1Elems,
            step2Elems,
            priceHtmlBlock = '',
            i,
            priceTableTh = '',
            priceTableBody = '',
            reserveButtons,
            roomCategoryInput,
            addressField,
            commentField,
            formData,
            ccSelect,
            ptSelect,
            income = {
                contractConditions : {},  //массив условий договора для селекта
                roomTypes : {}, //массив комнат с вол-вом свободных без названий
                payTypes : {},
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
                       <label for="hotelId">Hotel</label>
                       <select id="hotelId" name="hotelId" class="form-control form-control-sm tm-popup-task-form-textbox bx-focus" disabled>
                           <option value="">Select...</option>
                           <option value="1" selected>Hotel 1</option>
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
                
               
               <input type="hidden" name="companyId" id="companyId" value="">
               <input type="hidden" name="companyName" id="companyName" value="">
               <input type="hidden" name="companyCodeId" id="companyCodeId" value="">
               <input type="hidden" name="roomCategory" id="roomCategory" value="">
                
               <div class="ui-btn-container ui-btn-container-center text-right hidden-input">
                  <button type="button" id="servio_step1" class="mt-2 ui-btn ui-btn-primary-dark ui-btn-right ui-btn-icon-business">
                      Next Step
                  </button>
               </div>
                      
               <div class="form-row servio-step-1-elem hidden-input">      
                   <div class="form-group col-sm">
                       <label for="contractCondition">Contract Condition</label>
                       <select id="contractCondition" name="contractCondition" class="form-control form-control-sm tm-popup-task-form-textbox bx-focus">
                       </select>
                   </div>
               </div>
                
               <div class="form-row servio-step-1-elem hidden-input">
                   <div class="form-group col-sm">
                      <label for="paidType">Paid Type</label>
                      <select id="paidType" name="paidType" class="form-control form-control-sm tm-popup-task-form-textbox bx-focus">
                      </select>
                   </div>
               </div>
                
               <div class="ui-btn-container ui-btn-container-center text-right hidden-input">
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
                  
             <!--  <button type="button" id="add_servio_reserve" class="mt-2 ui-btn ui-btn-danger-dark ui-btn-icon-cloud">
                  Reserve!
               </button>-->
            </form>`

        popupObj = self.makePopupV2('servio-hotel-reservation', html, 'Hotel Reservation')
        form = document.getElementById('servio_popup')
        roomCategoryInput = document.getElementById('roomCategory')

        searchBtn = document.getElementById('servio_search')
        nextStepBtn = document.getElementById('servio_step1')
        step1Elems = document.querySelectorAll('.servio-step-1-elem')
        step2Elems = document.querySelectorAll('.reserve-hidden')

        ccSelect = document.getElementById('contractCondition')
        ptSelect = document.getElementById('paidType')

        priceHtmlBlock = document.getElementById('servio_price_info')

        const popupContent = document.getElementById('popup-window-content-servio-hotel-reservation')

        //получение данных компании
        BX.ajax.runAction('ourcompany:servio.nmspc.handler.companyData', {
            data: {}
        })
            .then(
                function (companyResponse) {
                    console.log('Promise Company',companyResponse);

                    if(companyResponse.data.error != false)
                    {
                        self.addErrorsBeforeFormNew(form, companyResponse.data.error, 'error')
                    }
                    else
                    {
                        // console.log('Next Step',companyResponse.data.result);

                        if(companyResponse.data.result)
                        {
                            let companyIdField = document.getElementById('companyId'),
                                companyNameField = document.getElementById('companyName'),
                                companyCodeIdField = document.getElementById('companyCodeId')

                            if (companyIdField !== null) {
                                companyIdField.value = companyResponse.data.result.CompanyID || 0;
                            }
                            if (companyNameField !== null) {
                                companyNameField.value = companyResponse.data.result.CompanyName;
                            }
                            if (companyCodeIdField !== null) {
                                companyCodeIdField.value = companyResponse.data.result.CompanyCodeID;
                            }

                            if(nextStepBtn !== null)
                            {
                                //показываем кнопку "Next Step"
                                self.toggleShowDOM(nextStepBtn.closest('div'),true)
                            }
                        }
                    }
                })
            .catch(
                //цикл вывода ошибок!!!
                (companyResponse) =>
                {
                    // console.log('ERR',companyResponse);
                    for(let cErr of companyResponse.errors)
                    {
                        self.addErrorsBeforeFormNew(form, cErr.message, 'error')
                    }
                }
            )

        //нажатие на Next Btn
        nextStepBtn.onclick = () =>
        {
            self.toggleClockLoaderToBtn(nextStepBtn)

            //данные формы
            formData = self.getFromFieldsData(form)

            // console.log('valid start!',formData)

            //валидация полей(кроме CC и PT)
            if(!self.validateForm(form,popupContent,formData)){
                //скрываем поля типа оплаты и условий контракта

                self.toggleClockLoaderToBtn(nextStepBtn)

                //скрываем селекты сс && pt
                self.toggleShowNodeList(step1Elems)

                //скрывааем кнопку Search
                self.toggleShowDOM(searchBtn.closest('div'))
            }
            else
            {
                //получаем contract conditions + paid type
                BX.ajax.runAction('ourcompany:servio.nmspc.handler.contractConditions', {
                    data: {
                        fields: formData
                    }
                })
                    .then(
                        function(CcAjaxResult)
                        {
                            console.log('chain 3.1, Cc & Pt',CcAjaxResult);

                            if(CcAjaxResult.data.error)
                            {
                                self.addErrorsBeforeFormNew(form, CcAjaxResult.data.error, 'error')

                                //скрываем селекты сс && pt
                                self.toggleShowNodeList(step1Elems)

                                //скрывааем кнопку Search
                                self.toggleShowDOM(searchBtn.closest('div'))

                                self.toggleClockLoaderToBtn(nextStepBtn)
                            }
                            else
                            {
                                income.contractConditions = CcAjaxResult.data.result.ContractConditions
                                income.roomTypes = CcAjaxResult.data.result.RoomTypes
                                income.payTypes = CcAjaxResult.data.result.PayTypes

                                //убираем часики скрываем кнопку "Next Step"
                                self.toggleClockLoaderToBtn(nextStepBtn)
                                self.toggleShowDOM(nextStepBtn.closest('div'))

                                //показываем кнопку Search
                                self.toggleShowDOM(searchBtn.closest('div'),true)

                                //отображение 2х селектов: cc и pt
                                self.toggleShowNodeList(step1Elems,true)

                                //вставка значений в селекты cc && pt

                                //селект cc
                                // let cCoptions = `<option value="0">System</option>`
                                let cCoptions = ``
                                if(CcAjaxResult.data.result.ContractConditions.length > 0)
                                {
                                    for(let cC of CcAjaxResult.data.result.ContractConditions)
                                    {
                                        cCoptions += `<option value="${cC.ContractConditionID}">${cC.ContractConditionName}</option>`
                                    }
                                }

                                if(ccSelect !== null)
                                {
                                    ccSelect.innerHTML = cCoptions
                                }

                                //селекты pt
                                if(Object.keys(income.payTypes).length > 0)
                                {
                                    if(ptSelect !== null)
                                    {
                                        ptSelect.innerHTML = self.optionsForPayTypeField(income.payTypes,ccSelect.value)
                                    }
                                }
                            }
                        }
                    )
                    .catch(
                        function (CcAjaxResult) {
                            console.log('Contract ERR',CcAjaxResult)

                            //вывод ошибок в окно
                            for(let ccErr of CcAjaxResult.errors)
                            {
                                self.addErrorsBeforeFormNew(form, ccErr.message, 'error')
                            }
                            self.toggleClockLoaderToBtn(nextStepBtn)
                        }
                    )
            }
        }


        //Нажатие Search
        searchBtn.onclick = () =>
        {
            console.log('Search Start');

            //очищаем список с ценами
            priceHtmlBlock.innerHTML = ''

            //данные формы
            formData = self.getFromFieldsData(form)

            self.toggleClockLoaderToBtn(searchBtn)

            //валидация полей(кроме CC и PT)
            if(!self.validateForm(form,popupContent,formData,'all')){

                // здесь НЕ скрываем поля типа оплаты и условий контракта
                self.toggleClockLoaderToBtn(searchBtn)
            }
            else
            {
                BX.ajax.runAction('ourcompany:servio.nmspc.handler.pricesByFilter', {
                    data: {
                        FIELDS: formData,
                        DEAL_ID: self.deal.id,
                    }
                })
                    .then(
                        (responseObj) =>
                        {
                            addressField = document.getElementById('address')
                            commentField = document.getElementById('comment')

                            console.log('Success Prices',responseObj)

                            if(Object.keys(responseObj.data.table).length > 0)
                            {
                                i = 1

                                self.categoriesObj = responseObj.data.table

                                priceTableBody = ''
                                Object.entries(responseObj.data.table).forEach(([key, row]) => {
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

                                priceHtmlBlock.innerHTML = priceTableTh

                                reserveButtons = document.querySelectorAll('.add-reserve')

                                if(reserveButtons.length > 0)
                                {
                                    reserveButtons.forEach(reserveButton => {
                                        reserveButton.onclick = function(){

                                            //Вставляем в поле ID выбранной категории комнат
                                            roomCategoryInput.value = this.dataset.categoryId

                                            formData = self.getFromFieldsData(form)

                                            if(!self.validateForm(form,popupContent,formData)) {
                                                //скрываем поля типа оплаты и условий контракта
                                            }
                                            else
                                            {
                                                // self.addReservation(popupObj,reserveButtons,this)

                                                self.addReservationNew(popupObj,reserveButtons,this,formData,form)
                                            }

                                        }
                                    })
                                }


                                if(addressField != null && responseObj.data.fields.ADDRESS.length > 0 && addressField.value.trim().length == 0)
                                {
                                    addressField.value = responseObj.data.fields.ADDRESS
                                }
                                if(commentField != null && responseObj.data.fields.COMMENTS.length > 0 && commentField.value.trim().length == 0)
                                {
                                    commentField.value = responseObj.data.fields.COMMENTS
                                }

                                self.toggleShowNodeList(step2Elems,true)
                            }
                            else
                            {
                                for(let error of responseObj.data.errors)
                                {
                                    self.addErrorsBeforeFormNew(form, error, 'error')
                                }
                            }

                            self.toggleClockLoaderToBtn(searchBtn)
                        }
                    )
                    .catch(
                        (responseObj) =>
                        {
                            console.log('ERR get Prices',responseObj)

                            for(let ccErr of responseObj.errors)
                            {
                                self.addErrorsBeforeFormNew(form, ccErr.message, 'error')
                            }
                            self.toggleClockLoaderToBtn(searchBtn)
                        }
                    )
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

        //5. изменение поля СС
        ccSelect.onchange = function()
        {
            if(ccSelect.value.trim().length > 0)
            {
                //ptSelect
                if(ptSelect !== null)
                {
                    ptSelect.innerHTML = self.optionsForPayTypeField(income.payTypes,ccSelect.value)
                }
            }
            else
            {
                ptSelect.innerHTML = ''
            }
        }


        popupObj.show()
    }

    validateForm(form,popupContent,formData,checkFlag = 'notAll')
    {
        let errorFlag = true,
            inptField

        //удаление ошибок и сообщений
        this.deleteErrorsinForm(popupContent);

        if(form !== null)
        {
            //удаление рамок и ошибок
            let formFileldsElems = form.querySelectorAll('input,select,textarea')
            if(formFileldsElems.length > 0)
            {
                for(inptField of formFileldsElems)
                {
                    if(inptField.classList.contains('my-error-field'))
                    {
                        inptField.classList.remove('my-error-field')
                    }
                }
            }

            console.log('validate Form Data',formData);

            //валидация и вывод ошибок

            if(formData.dateFrom.trim() == '' )
            {
                const df = form.querySelector('#dateFrom')
                df.classList.add('my-error-field')
                this.addErrorsBeforeFormNew(form,`Fill Date From field!`, 'error')
                errorFlag = false
            }
            if(formData.dateTo.trim() == '' )
            {
                const dt = form.querySelector('#dateTo')
                dt.classList.add('my-error-field')
                this.addErrorsBeforeFormNew(form,`Fill Date To field!`, 'error')
                errorFlag = false
            }
            if(formData.adults.trim() == '' )
            {
                const adt = form.querySelector('#adults')
                adt.classList.add('my-error-field')
                this.addErrorsBeforeFormNew(form,`Set adults number > 0!`, 'error')
                errorFlag = false
            }
            if(formData.childs.trim() == '' )
            {
                const chlds = form.querySelector('#childs')
                chlds.classList.add('my-error-field')
                this.addErrorsBeforeFormNew(form,`Set childs number or 0!`, 'error')
                errorFlag = false
            }


            //это валидация всех полей формы, если нужно(по кнопке Search)
            if(checkFlag === 'all')
            {
                // contractCondition
                if(formData.contractCondition.trim() == '' )
                {
                    const cconds = form.querySelector('#childs')
                    cconds.classList.add('my-error-field')
                    this.addErrorsBeforeFormNew(form,`Choose Contract Condition!`, 'error')
                    errorFlag = false
                }

                if(formData.paidType.trim() == '' )
                {
                    const pt = form.querySelector('#paidType')
                    pt.classList.add('my-error-field')
                    this.addErrorsBeforeFormNew(form,`Choose Paid Type!`, 'error')
                    errorFlag = false
                }

            }
            return (errorFlag === false) ? false : true;
        }
        return false
    }

    //формируем options
    optionsForPayTypeField(valuesObject,fieldValue)
    {
        let options = ``,
            pt
        if(valuesObject.hasOwnProperty(fieldValue.trim()))
        {
            for(pt of valuesObject[fieldValue.trim()])
            {
                options += `<option value="${pt.ID}">${pt.NAME}</option>`
            }
        }
        return options
    }


    loadServioPopupWithReserervationV4()
    {
        let self= this,
            servioBtn = document.getElementById('servio'),
            popupObj = {},
            html = `<div id="servio_reserve_view"></div>`,
            popupBody,
            servisesTable = '',
            servisesTableInner = '',
            reserveDataHtml = '',
            cancelBtn, confirmBtn, billBtn, billCheckboxes,
            k = 1,
            thService = '',
            tdServicePrice = '',
            clientServises = [], //массив всехсервисов для формирования общего счета по заказу
            clientServisesByDates = {} //объек, с разбивкой сервисов по дням

        popupObj = self.makePopupV2('servio-hotel-reservation-view',html,'Hotel Reservation View')
        popupBody = document.getElementById('servio_reserve_view')

        BX.ajax.runAction('ourcompany:servio.nmspc.handler.reserveData', {
            data: {
                RESERVE_ID: this.deal.reserveId
            }
        })
            .then(
                (reserveAjaxObj)=>
                {
                    console.log('SUCCESS',reserveAjaxObj);

                    if(reserveAjaxObj.data.errors.length > 0)
                    {
                        for(let error of reserveAjaxObj.data.errors)
                        {
                            reserveDataHtml +=
                                `<div class="ui-alert ui-alert-danger custom-error">
                                    <span class="ui-alert-message"><strong>Error! </strong>${error}</span>
                                </div>`
                        }
                    }
                    else
                    {
                        if(reserveAjaxObj.data.result.ResultServises.length > 0)
                        {
                            for(let thServ of reserveAjaxObj.data.result.ThServises)
                            {
                                thService += `<th>${thServ.ServiceName}, ${reserveAjaxObj.data.result.ValuteShort}</th>`
                            }

                            for(let resultPrices of reserveAjaxObj.data.result.ResultServises)
                            {

                                tdServicePrice = ''

                                for(let servPrice of resultPrices.ServicePrices)
                                {
                                    tdServicePrice += `<td>${servPrice}</td>`
                                }

                                servisesTableInner +=
                                    `<tr>
                                        <td>${k}</td>
                                        <td>${resultPrices.Date}</td>
                                        ${tdServicePrice}
                                        <td>${resultPrices.Price}</td>
                                        <td class="ui-alert ${(resultPrices.IsPaid === true) ? 'ui-alert-success' : 'ui-alert-danger'}">${resultPrices.IsPaid}</td>
    
                                        ${
                                        (resultPrices.IsPaid !== true)
                                            ?`<td>
                                                <label class="ui-ctl ui-ctl-checkbox">
                                                    <input type="checkbox" name="${resultPrices.Date}" class="ui-ctl-element servio-date-bill" value="${resultPrices.Date}">
                                                </label>
                                              </td>`
                                            : `<td></td>`
                                        }
                                    </tr>`
                                k++
                            }

                            servisesTable =
                                `<div class="row">
                                    <form id="servio-dates-bill" name="servio-dates-bill">
                                        <table class="table table-sm table-responsive text-center">
                                            <thead>
                                                <th>#</th>
                                                <th>Date</th>
                                                ${thService}
                                                <th>Total Day Price, ${reserveAjaxObj.data.result.ValuteShort}</th>
                                                <th>Is Paid</th>
                                                <th>Add To Bill</th>
                                            </thead>
                                            <tbody>
                                                ${servisesTableInner}
                                            </tbody>
                                        </table>
                                    </form>
                                </div>`
                        }

                        reserveDataHtml =
                            `<div class="row">
                            <div class="col-sm-6 ui-alert ui-alert-default">Serviceprovider Name</div>
                                <div class="col-sm-6 ui-alert ui-alert-primary">
                                    <span class="ui-alert-message"><strong>${reserveAjaxObj.data.result.ServiceProviderName}</strong></span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6 ui-alert ui-alert-default">Reserve Status</div>
                                <div class="col-sm-6 ui-alert ui-alert-danger">
                                    <span class="ui-alert-message"><strong>${reserveAjaxObj.data.result.StatusName}</strong></span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6 ui-alert ui-alert-default">Account Name</div>
                                <div class="col-sm-6 ui-alert ui-alert-primary">${reserveAjaxObj.data.result.AccountName}</div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6 ui-alert ui-alert-default">Email</div>
                                <div class="col-sm-6 ui-alert ui-alert-primary">${reserveAjaxObj.data.result.Email}</div>
                            </div>
                              <div class="row">
                                <div class="col-sm-6 ui-alert ui-alert-default">Date From</div>
                                <div class="col-sm-6 ui-alert ui-alert-primary">${reserveAjaxObj.data.result.DateArrival}</div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6 ui-alert ui-alert-default">Date To</div>
                                <div class="col-sm-6 ui-alert ui-alert-primary">${reserveAjaxObj.data.result.DateDeparture}</div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6 ui-alert ui-alert-default">Adults</div>
                                <div class="col-sm-6 ui-alert ui-alert-primary">${reserveAjaxObj.data.result.Adults}</div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6 ui-alert ui-alert-default">Childs</div>
                                <div class="col-sm-6 ui-alert ui-alert-primary">${reserveAjaxObj.data.result.Childs}</div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6 ui-alert ui-alert-default">Room Type</div>
                                <div class="col-sm-6 ui-alert ui-alert-primary">${reserveAjaxObj.data.result.RoomType}</div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6 ui-alert ui-alert-default">Paid Type</div>
                                <div class="col-sm-6 ui-alert ui-alert-primary">${reserveAjaxObj.data.result.PaidTypeText}</div>
                            </div>
                            
                            ${servisesTable}
                                    
                                    
                            ${
                                (Number(self.deal.reserveId) > 0)
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
                                    
                            <button class="ui-btn ui-btn-secondary hidden-input" id="getReserveBill">Счет</button>`

                        //сервисы по датам и просто массив (для формирования счетов)
                        clientServises = reserveAjaxObj.data.result.Services
                        clientServisesByDates = reserveAjaxObj.data.result.ServicesByDates

                    }



                    popupBody.innerHTML = reserveDataHtml

                    //НАЖАТИЕ НА КНОПКИ
                    cancelBtn = document.getElementById('reserveCancelBtn')
                    confirmBtn = document.getElementById('reserveAcceptBtn')
                    // billBtn = document.getElementById('getReserveBill')
                    billCheckboxes = document.querySelectorAll('.servio-date-bill')

                    if(cancelBtn !== null)
                    {
                        cancelBtn.onclick = () =>
                        {
                            console.log('cancelBtn');
                            self.cancelReserveV2(popupObj,cancelBtn)
                        }
                    }

                    if(confirmBtn !== null)
                    {
                        confirmBtn.onclick = () =>
                        {
                            console.log('confirmBtn');
                            self.confirmReserveV2(confirmBtn)
                            // self.testGetDocument(28199)
                        }
                    }

                    if(billCheckboxes !== null)
                    {
                        billCheckboxes.forEach(checkbox => {

                            // console.log('checkbox',checkbox);
                            checkbox.onclick = function()
                            {
                                // self.showBillBtn(billBtn,billCheckboxes);
                                self.showBillBtn(clientServises);
                            }
                        })

                    }

                    // if(billBtn !== null)
                    // {
                    //     billBtn.onclick = () =>
                    //     {
                    //         console.log('billBtn New');
                    //         self.getBillForReserveV2(billBtn,clientServises)
                    //     }
                    // }

                }
            )
            .catch(
                (reserveAjaxObj)=>
                {
                    console.log('ERRORS',reserveAjaxObj);

                    for(let ccErr of reserveAjaxObj.errors)
                    {
                        reserveDataHtml +=
                            `<div class="ui-alert ui-alert-danger custom-error">
                                <span class="ui-alert-message"><strong>Error! </strong>${ccErr.message}</span>
                            </div>`
                    }

                    popupBody.innerHTML = reserveDataHtml
                }
            )

        //show popup
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
            cancelBtn, confirmBtn, billBtn,
            k = 1


        popupObj = self.makePopupV2('servio-hotel-reservation-view',html,'Hotel Reservation View',)

        //получаем данные резерва
        this.makeAjaxRequest(this.url.ajax, {'ACTION': 'GET_RESERVE_BY_ID', 'RESERVE_ID': this.deal.reserveId},
            function (response) {

                console.log('Load Reserve',response);
                popupBody = document.getElementById('servio_reserve_view')

                // if(popupBody !== null)
                // {}

                if(response.error.length > 0)
                {
                    reserveDataHtml =
                        `<div class="ui-alert ui-alert-danger custom-error">
                            <span class="ui-alert-message"><strong>Error! </strong>${response.error}</span>
                        </div>`
                }
                else
                {
                    if(response.result.ResultServises.length > 0)
                    {

                        //Разбор сервисов в th, td
                        let thService = '',
                            tdServicePrice = ''

                        for(let thServ of response.result.ThServises)
                        {
                            thService += `<th>${thServ.ServiceName}, ${response.result.ValuteShort}</th>`
                        }

                        for(let resultPrices of response.result.ResultServises){

                            tdServicePrice = ''

                            for(let servPrice of resultPrices.ServicePrices)
                            {
                                tdServicePrice += `<td>${servPrice}</td>`
                            }

                            servisesTableInner +=
                                `<tr>
                                    <td>${k}</td>
                                    <td>${resultPrices.Date}</td>
                                    ${tdServicePrice}
                                    <td>${resultPrices.Price}</td>
                                    <td class="ui-alert ${(resultPrices.IsPaid === true) ? 'ui-alert-success' : 'ui-alert-danger'}">${resultPrices.IsPaid}</td>

                                    ${
                                    (resultPrices.IsPaid !== true)
                                        ?`<td><button class="ui-btn ui-btn-xs ui-btn-primary pay-the-day data-account-id="${resultPrices.CustomerAccount}">To Pay</button></td>`
                                        : `<td></td>`
                                    }
                                </tr>`
                            k++
                        }

                        servisesTable =
                            `<div class="row">
                                <table class="table table-sm table-responsive text-center">
                                    <thead>
                                        <th>#</th>
                                        <th>Date</th>
                                        ${thService}
                                        <th>Total Day Price, ${response.result.ValuteShort}</th>
                                        <th>Is Paid</th>
                                        <th></th>
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
                                    <span class="ui-alert-message"><strong>${response.result.ServiceProviderName}</strong></span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6 ui-alert ui-alert-default">Reserve Status</div>
                                <div class="col-sm-6 ui-alert ui-alert-danger">
                                    <span class="ui-alert-message"><strong>${response.result.StatusName}</strong></span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6 ui-alert ui-alert-default">Account Name</div>
                                <div class="col-sm-6 ui-alert ui-alert-primary">${response.result.AccountName}</div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6 ui-alert ui-alert-default">Email</div>
                                <div class="col-sm-6 ui-alert ui-alert-primary">${response.result.Email}</div>
                            </div>
                              <div class="row">
                                <div class="col-sm-6 ui-alert ui-alert-default">Date From</div>
                                <div class="col-sm-6 ui-alert ui-alert-primary">${response.result.DateArrival}</div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6 ui-alert ui-alert-default">Date To</div>
                                <div class="col-sm-6 ui-alert ui-alert-primary">${response.result.DateDeparture}</div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6 ui-alert ui-alert-default">Adults</div>
                                <div class="col-sm-6 ui-alert ui-alert-primary">${response.result.Adults}</div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6 ui-alert ui-alert-default">Childs</div>
                                <div class="col-sm-6 ui-alert ui-alert-primary">${response.result.Childs}</div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6 ui-alert ui-alert-default">Room Type</div>
                                <div class="col-sm-6 ui-alert ui-alert-primary">${response.result.RoomType}</div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6 ui-alert ui-alert-default">Paid Type</div>
                                <div class="col-sm-6 ui-alert ui-alert-primary">${response.result.PaidTypeText}</div>
                            </div>
                            
                            ${servisesTable}
                                    
                                    
                            ${
                            (Number(self.deal.reserveId) > 0)
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


                // console.log('OOOOOO',self.deal);
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
                        // console.log('cancelBtn');

                        self.cancelReserve(popupObj,cancelBtn)
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

    //отображение/скрытие 2х полей - old
    // toggleReserveFields(flag)
    // {
    //     let form = document.getElementById('servio_popup'),
    //         hiddenBlocks = document.querySelectorAll('.reserve-hidden')
    //
    //     if(hiddenBlocks.length > 0 )
    //     {
    //         hiddenBlocks.forEach(elem => {
    //             // console.log(222,elem);
    //             if(flag === true /*&& elem.classList.contains('hidden-input')*/)
    //             {
    //                 elem.classList.remove('hidden-input')
    //             }
    //             else
    //             {
    //                 elem.classList.add('hidden-input')
    //             }
    //         })
    //     }
    //     // console.log('UUUU',hiddenBlocks);
    //
    // }

    toggleShowDOM(domObj,flag)
    {

        // if(domObj.constructor === Object)
        // {
        if(flag === true)
        {
            domObj.classList.remove('hidden-input')
        }
        else
        {
            domObj.classList.add('hidden-input')
        }
        // }
    }

    //отображение/скрытие 2х полей
    toggleShowNodeList(nodeObj,flag)
    {
        if(nodeObj.length > 0 )
        {
            nodeObj.forEach(elem => {
                if(flag === true)
                {
                    elem.classList.remove('hidden-input')
                }
                else
                {
                    elem.classList.add('hidden-input')
                }
            })
        }
    }

    // testGetDocument(docId)
    // {
    //     this.makeAjaxRequest(this.url.ajax, {'ACTION': 'TEST_GET_DOCUMENT', 'DOCUMENT_ID': docId},
    //         function (response) {
    //             console.log('TEST_GET_DOCUMENT', response)
    //         }
    //     )
    // }


    cancelReserveV2(popupObj,cancelBtn)
    {
        let self = this,
            viewPopup = document.getElementById('servio_reserve_view'),
            firstRow = viewPopup.querySelector('.row')

        //удаление ошибок и сообщений
        this.deleteErrorsinForm(viewPopup);

        this.toggleClockLoaderToBtn(cancelBtn)

        BX.ajax.runAction('ourcompany:servio.nmspc.handler.abortReserve', {
            data: {
                FIELDS: this.deal
            }
        })
            .then(
                (abortRequestObj) =>
                {
                    // console.log('Success Abort reserve result',abortRequestObj);

                    if(abortRequestObj.data.errors.length > 0)
                    {
                        for(let error of abortRequestObj.data.errors)
                        {
                            self.addErrorsBeforeFormNew(firstRow,error,'error')
                        }

                        // self.toggleClockLoaderToBtn(cancelBtn)
                    }
                    else
                    {
                        self.addErrorsBeforeFormNew(firstRow,'Резерв отменен!','success')

                        // self.toggleClockLoaderToBtn(cancelBtn)
                        // self.disableElement(cancelBtn)

                        setTimeout(() =>
                        {
                            popupObj.destroy()

                            self.deal.reserveId = abortRequestObj.data.result
                            location.reload()
                        }, 2000)
                    }
                    self.toggleClockLoaderToBtn(cancelBtn)
                }
            )
            .catch(
                (abortRequestObj) =>
                {
                    // console.log('Error Abort reserve result',abortRequestObj);

                    for(let ccErr of abortRequestObj.errors)
                    {
                        self.addErrorsBeforeFormNew(firstRow, ccErr.message, 'error')
                    }

                    self.toggleClockLoaderToBtn(cancelBtn)
                }
            )
    }

    //old
    // cancelReserve(popupObj,cancelBtn)
    // {
    //     let self = this,
    //         viewPopup = document.getElementById('servio_reserve_view'),
    //         firstRow = viewPopup.querySelector('.row')
    //
    //     //удаление ошибок и сообщений
    //     self.deleteErrorsinForm(viewPopup);
    //
    //     self.toggleClockLoaderToBtn(cancelBtn)
    //
    //     this.makeAjaxRequest(this.url.ajax, {'ACTION': 'ABORT_RESERVE', 'FIELDS': self.deal},
    //         function (response) {
    //             console.log('Abort Ajax:',response)
    //
    //             if(response.error)
    //             {
    //                 self.addErrorsBeforeFormNew(firstRow,response.error,'error')
    //                 self.toggleClockLoaderToBtn(cancelBtn)
    //
    //             }
    //             else
    //             {
    //                 self.addErrorsBeforeFormNew(firstRow,'Резерв отменен!','success')
    //
    //                 self.toggleClockLoaderToBtn(cancelBtn)
    //
    //                 setTimeout(() => {
    //                     popupObj.destroy()
    //
    //                     self.deal.reserveId = response.result
    //                     location.reload()
    //                 }, 2000)
    //             }
    //
    //         }
    //     )
    //
    //     console.log('Test Abort Reserve');
    // }


    confirmReserveV2(btnObj)
    {
        let self = this,
            viewPopup = document.getElementById('servio_reserve_view'),
            firstRow = viewPopup.querySelector('.row')

        this.deleteErrorsinForm(viewPopup)

        this.toggleClockLoaderToBtn(btnObj)

        BX.ajax.runAction('ourcompany:servio.nmspc.handler.confirmReserve', {
            data: {
                FIELDS: this.deal
            }
        })
            .then(
                (confirmRequestObj) =>
                {
                    console.log('Success Confirm reserve result',confirmRequestObj);

                    if(confirmRequestObj.data.errors.length > 0)
                    {
                        for(let error of confirmRequestObj.data.errors)
                        {
                            self.addErrorsBeforeFormNew(firstRow,error,'error')
                        }

                        self.toggleClockLoaderToBtn(btnObj)
                    }
                    else
                    {
                        self.addErrorsBeforeFormNew(firstRow,'Файл потверждения сохранен в сделке! Обновите страницу!','success')
                        self.toggleClockLoaderToBtn(btnObj)
                        self.disableElement(btnObj)
                    }
                }
            )
            .catch(
                (confirmRequestObj) =>
                {
                    console.log('Error Confirm reserve result',confirmRequestObj);

                    for(let ccErr of confirmRequestObj.errors)
                    {
                        self.addErrorsBeforeFormNew(firstRow, ccErr.message, 'error')
                    }

                    self.toggleClockLoaderToBtn(btnObj)
                }
            )

    }

    //old
    // confirmReserve(btnObj)
    // {
    //     let self = this,
    //         viewPopup = document.getElementById('servio_reserve_view'),
    //         firstRow = viewPopup.querySelector('.row')
    //
    //     this.deleteErrorsinForm(viewPopup)
    //
    //     this.toggleClockLoaderToBtn(btnObj)
    //
    //     this.makeAjaxRequest(this.url.ajax, {'ACTION': 'CONFIRM_RESERVE', 'FIELDS': self.deal},
    //         function (response) {
    //             console.log('CONFIRM RESERVE', response)
    //
    //             if(response.error)
    //             {
    //                 self.addErrorsBeforeFormNew(firstRow,response.error,'error')
    //             }
    //             else
    //             {
    //                 self.addErrorsBeforeFormNew(firstRow,'Файл потверждения сохранен в сделке! Обновите страницу!','success')
    //             }
    //
    //             self.disableElement(btnObj)
    //             self.toggleClockLoaderToBtn(btnObj)
    //         }
    //     )
    // }

    showBillBtn(clientServises)
    {
        let self = this,
            selected,
            billDatesForm = document.getElementById('servio-dates-bill'),
            billBtn = document.getElementById('getReserveBill'),
            selectedDates = []

        // console.log(this.getFromFieldsData());
        // console.log(billDatesForm.length);

        Object.entries(billDatesForm.elements).forEach(([index,elem]) => {
            if(elem.name !== '')
            {
                if(elem.checked === true )
                {
                    selectedDates.push(elem.value)
                }
            }
        });

        if(selectedDates.length > 0)
        {
            //покахываем кнопку счета
            this.toggleShowDOM(billBtn,true)
        }
        else
        {
            //скрываем кнопку счета
            this.toggleShowDOM(billBtn)
        }

        console.log('selectedDates chbxs',selectedDates,clientServises);

        if(billBtn !== null)
        {
            billBtn.onclick = () =>
            {
                // console.log('billBtn New');
                // self.getBillForReserveV2(billBtn,clientServises)
                self.getBillForReserveV2(billBtn,selectedDates,clientServises)
            }
        }


        // console.log('get for work!',document.forms['servio-dates-bill'])
    }

    // getBillForReserveV2(btnObj,servises)
    getBillForReserveV2(btnObj,selectedDates,clientServises)
    {
        let self = this,
            viewPopup = document.getElementById('servio_reserve_view'),
            firstRow = viewPopup.querySelector('.row')

        this.deleteErrorsinForm(viewPopup)

        this.toggleClockLoaderToBtn(btnObj)

        BX.ajax.runAction('ourcompany:servio.nmspc.handler.createBillReserve', {
            data: {
                FIELDS: this.deal,
                SERVICES: clientServises,
                DATES: selectedDates,
                FIRST_PAY: '2799.00'
            }
        })
            .then(
                (createBillRequestObj) =>
                {
                    console.log('Success Create Bill reserve result',createBillRequestObj);

                    self.toggleClockLoaderToBtn(btnObj)
                }
            )
            .catch(
                (createBillRequestObj) =>
                {
                    console.log('Error Create Bill reserve result',createBillRequestObj);

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