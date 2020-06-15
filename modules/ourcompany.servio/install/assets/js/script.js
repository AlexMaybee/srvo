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
            adults: 0,
            childs: 0,
            companyId: 0,
        }
        this.list = {
            roomCategory: [],
            errors: [],
            categoryPriceData: {},
        }
        this.reserve = {
            reserveId: 0,
            reserveData: {},
        }
        this.company = {
            id: 0,
            title: '',
        }


        /*
        * По шагам
        * */

        // 1. Заполнение полей "с" и "по" текущими датой и + 1 день
        this.fillDatesOnStart()

        // 2. Запрос данных компании
        this.getCompanyInfo()

        // // 3. Запрос данных по фильтрам
        // this.getRoomsByFilter()

        //test request
        // this.makeAjaxRequest(this.url.ajax,{'test data':'lol'},function (response) {
        //     console.log('callback function from ajax',response)
        // })

        let servioBtn = document.getElementById('servio') //.addEventListener('click',this.makePopup({'yyy':'iii'}))





        if(servioBtn !==  null)
        {
            let self = this
            servioBtn.onclick = () => {

                // 3. Запрос данных по фильтрам
                this.getRoomsByFilter()



                let htmlContent = this.returnFormHtmlInPopup()


                this.makePopup('servio-hotel-reservation',htmlContent,'Hotel Reservation')


                //4.Изменение поля Date_From
                let dateFrom = document.getElementById('dateFrom')
                if(dateFrom !== null)
                {
                    dateFrom.onchange =  function () {
                        self.changeDateStart();
                    }
                }

                //5. Изменение поля Date_To
                let dateTo = document.getElementById('dateTo')
                if(dateTo !== null)
                {
                    dateTo.onchange = function () {
                        self.changeFinishDate()
                    }
                }

                // 6,7 Изменение значений в полях Adults и Childs
                let adultsAndChildFields = document.querySelectorAll('#adults, #childs')
                if(adultsAndChildFields.length > 0)
                {
                    let roomCatField = document.getElementById('roomCategory')
                    for(let elem of adultsAndChildFields)
                    {
                        //запрос категорий при изменении значений полей
                        elem.onchange = function () {
                            self.changeTextFields()
                        }

                        //удаление из полей всего кроме цифр
                        elem.onkeyup = function () {
                            self.clearAllExeptnums(this)
                        }
                    }
                }

            }
        }

        console.log('FILTERS',this.filters);

    }

    /*
    * Fill dates on page start
    * */
    fillDatesOnStart()
    {
        let self = this,
            dateStart = new Date(),
            dateFinish = new Date();

        dateFinish.setDate(dateFinish.getDate()  + 1);

        // console.log(this.createDate(new Date('2020-06-04')));

        this.filters.dateFrom = this.createDate(dateStart);
        this.filters.dateTo = this.createDate(dateFinish);
    }


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

    returnFormHtmlInPopup()
    {

        // console.log('RENDER',this.list.errors.length);

        let errors = '';

        if(this.list.errors.length > 0)
        {
            for(let err of this.list.errors)
            {
                // console.log('ERRRRR',err)

                errors += `
                    <div class="ui-alert ui-alert-danger">
                        <span class="ui-alert-message"><strong>Error!</strong> ${err}</span>
                    </div>
                    `
            }
        }



        return `

            ${errors}

            <form id="servio_popup" onsubmit="return false" autocomplete="off">
               
                <div class="form-row">
                    <div class="col-sm-6 form-group">
                        <label for="dateFrom" class="col-form-label-sm">Date From</label>
                        <input type="date" name="dateFrom" class="form-control form-control-sm tm-popup-task-form-textbox bx-focus" id="dateFrom" title="Select date from" autocomplete="off"
                            value="${this.filters.dateFrom}"
                            >
                    </div>
                    <div class="col-sm-6 form-group">
                        <label for="dateTo" class="col-form-label-sm">Date To</label>
                        <input type="date" name="dateTo" class="form-control form-control-sm tm-popup-task-form-textbox bx-focus" id="dateTo" title="Select date from" autocomplete="off"
                            value="${this.filters.dateTo}"
                            >
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-sm">
                        <label for="adults">Adults</label>
                        <input id="adults" name="adults" class="form-control form-control-sm tm-popup-task-form-textbox bx-focus"
                               value="${this.filters.adults}"
                               >
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group col-sm">
                        <label for="childs">Childs</label>
                        <input id="childs" name="childs" class="form-control form-control-sm tm-popup-task-form-textbox bx-focus"
                                value="${this.filters.childs}"
                               >
                    </div>
                </div>
                
                <div class="form-row">
                   <div class="form-group col-sm">
                       <label for="roomCategory">Room Category</label>
                       <select id="roomCategory" name="roomCategory" class="form-control form-control-sm tm-popup-task-form-textbox bx-focus"
                                onchange=""
                              >
                           <option value="">Select...</option>
                           <option value="1">One</option>
                           <option value="2">Two</option>
                       </select>
                   </div>
                </div>
                  
                <div id="servio_price_info">
                    <table class="table table-sm">
                        <thead class="thead-secondary">
                            <tr>
                                <th scope="col-sm">#</th>
                                <th scope="col-sm">First</th>
                                <th scope="col-sm">Last</th>
                                <th scope="col-sm">Handle</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th scope="row">1</th>
                                <td>Mark</td>
                                <td>Otto</td>
                                <td>@mdo</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                  
                 <button type="button" id="add_servio_reserve" class="mt-2 ui-btn ui-btn-danger-dark ui-btn-icon-task ui-btn-round" 
                    onclick="">Reserve!</button>
        
                    
                  <div id="test_table"></div>
        
        
            </form>`
    }

    makePopup(popupTechName,htmlContent,popupTitle)
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
            events: {
                onPopupClose: function(PopupWindow) {
                    // Событие при закрытии окна
                    PopupProductProvider.destroy()
                }
            }
        })

        //вызов окна
        PopupProductProvider.show();
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
    getCompanyInfo()
    {
        let self = this

        this.makeAjaxRequest(this.url.ajax,{'ACTION' : 'GET_COMPANY_INFO'},
            function (response) {

                // console.log('COMPANY  callback function from ajax',response)
                if(response.error)
                {
                    self.list.errors.push(response.error);
                    // console.log('Company Error!!!',response.error);
                    // self.addErrorsBeforeForm(
                    //     `
                    //     <div class="ui-alert ui-alert-danger">
                    //         <span class="ui-alert-message"><strong>Error!</strong> ${response.error}</span>
                    //     </div>
                    //     `
                    // )
                }
                else
                {
                    self.company = response.result
                    self.filters.companyId = response.result.CompanyID
                }
            })

        // console.log('errors list',this.list.errors);
    }

    addErrorsBeforeForm(errText)
    {
        let form = document.getElementById('servio_popup');
        if(form !== null)
        {
            $(form).before(errText);
        }
    }

    takeFormData()
    {
        let form = document.getElementById('servio_popup'),
            elem
        if(form !== null)
        {
            for(elem of form.elements)
            {
                if(elem.name != '' && this.filters.hasOwnProperty(elem.name))
                {
                    this.filters[elem.name] = elem.value
                }
            }
        }

        return this.filters
    }

    getRoomsByFilter()
    {
        let data = {'ACTION' : 'GET_CATEGORIES_WITH_ROOMS', 'FIELDS':this.filters},
            self = this;

        this.makeAjaxRequest(this.url.ajax,data,
            function (response) {
                console.log('ROOMS',response);

                if(response.error ===  false)
                {
                    let roomSelect = document.getElementById('roomCategory'),
                        options = '<option value="">Select...</option>';

                    for(let option of response.result.rooms)
                    {
                        options += `<option value="${option.Id}">${option.CategoryName} (${option.FreeRoom} rooms)</option>`
                    }


                    // self.list.roomCategory = response.result.rooms;
                    // self.list.categoryPriceData = response.result.priceLists;
                    // self.showPriceLists(); //отображение на странице


                    roomSelect.innerHTML = options
                    self.list.roomCategory = response.result.rooms;
                    self.showPriceListsNew(response.result.priceLists); //отображение на странице
                }
                else
                {
                    self.list.errors.push(response.error);
                }

            })
    }

    showPriceListsNew(priceRowsArr)
    {
        let i = 1, tableHtml = '', body = '', row,
            // parentForm = document.getElementById('test_table')
            parentForm = document.getElementById('servio_price_info')

        if(Object.keys(priceRowsArr).length > 0)
        {
            for(let row of priceRowsArr)
            {
                body +=
                    `
                    <tr>
                        <td>${i}</td>
                        <td>${row.roomTypeName}</td>
                        <td>${row.dates}</td>
                        <td>${row.totalDays}</td>
                        <td>${row.totalPrice}, ${row.currency}</td>
                    </tr>
                    `
                i++
            }

            tableHtml =
                `
                <table class="table table-sm table-responsive">
                    <thead>
                        <tr>
                            <th scope="col-sm">#</th>                        
                            <th scope="col-sm">Type</th>                        
                            <th scope="col-sm">Dates</th>                        
                            <th scope="col-sm">Days Total</th>                        
                            <th scope="col-sm">Total Price</th>                        
                        </tr>   
                    </thead> 
                    <tbody> ${body}</tbody>
                </>   
                `

            if(parentForm !== null)
            {
                parentForm.innerHTML = tableHtml
            }

        }
    }

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
    changeDateStart()
    {
        let startDateField = document.getElementById('dateFrom'),
            finishDateField = document.getElementById('dateTo'),
            finishDate

        if(startDateField !== null && finishDateField !== null)
        {
            if(startDateField.value !== '')
            {
                if(startDateField.value == finishDateField.value)
                {
                    finishDate = new Date(startDateField.value)
                    finishDate.setDate(finishDate.getDate()  + 1)
                    finishDateField.value = this.createDate(finishDate);
                }
            }

            //ОБНОВЛЕНИЕ ПОЛЕЙ ОБЪЕКТА
            this.takeFormData();

            this.getRoomsByFilter()
        }
    }

    changeFinishDate()
    {
        let self = this,
            startDateField = document.getElementById('dateFrom'),
            finishDateField = document.getElementById('dateTo'),
            startDate

        if(startDateField !== null && finishDateField !== null)
        {
            if(finishDateField.value !== '' && finishDateField.value === startDateField.value)
            {
                startDate = new Date(finishDateField.value);
                startDate.setDate(startDate.getDate() - 1);
                startDateField.value = this.createDate(startDate);
            }
        }

        //ОБНОВЛЕНИЕ ПОЛЕЙ ОБЪЕКТА
        this.takeFormData();

        this.getRoomsByFilter();
    }

    changeTextFields()
    {
        //ОБНОВЛЕНИЕ ПОЛЕЙ ОБЪЕКТА
        this.takeFormData();
        this.getRoomsByFilter()
    }

    //удаляет все, кроме цифр
    clearAllExeptnums(obj)
    {
        obj.value = Number(obj.value.replace(/[^\d]/g,''))
    }

}





