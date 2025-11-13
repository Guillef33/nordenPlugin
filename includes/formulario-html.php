<section class="container">
    <form method="POST" action="/cotizar-auto" class="form-cotizar" onsubmit="mostrarLoader(event)">
        <h3>Datos del vehículo</h3>
        <div class="form-line">

            <div>
                <label for="condicion">Condicion</label>
                <select name="condicion" id="condicion">
                    <option value="0km">0KM</option>
                    <option value="usado">Usado</option>
                </select>
            </div>

            <div class="hidden" id="div-anios">
                <label for="anio">Año</label>
                <select type="text" name="anio" id="anio" for="anio" required>
                    <option value="" disabled="">Selecciona un año</option>
                    <option value="2025">2025</option>
                    <option value="2024">2024</option>
                    <option value="2023">2023</option>
                    <option value="2022">2022</option>
                    <option value="2021">2021</option>
                    <option value="2020">2020</option>
                    <option value="2019">2019</option>
                    <option value="2018">2018</option>
                    <option value="2017">2017</option>
                    <option value="2016">2016</option>
                    <option value="2015">2015</option>
                    <option value="2014">2014</option>
                    <option value="2013">2013</option>
                    <option value="2012">2012</option>
                    <option value="2011">2011</option>
                    <option value="2010">2010</option>
                    <option value="2009">2009</option>
                    <option value="2008">2008</option>
                    <option value="2007">2007</option>
                    <option value="2006">2006</option>
                </select>
            </div>

            <div>
                <label>Marcas</label>
                <select name="marcas" id="marca" required>
                    <option value="">Seleccionar Marca</option>
                    <?php if (!empty($marcas)): ?>
                        <?php foreach ($marcas['Data'] as $marca): ?>
                            <option value="<?= esc_attr($marca['Value']) ?>|<?= esc_attr($marca['Text']) ?>"><?= esc_html($marca['Text']) ?></option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="">No se pudieron cargar las marcas</option>
                    <?php endif; ?>
                </select>
            </div>

            <div>
                <label for="modelo">Modelo</label>
                <select name="modelo" id="modelo" for="modelo" required>
                    <option value="" disabled selected>Selecciona un modelo</option>
                    <?php if (isset($modelos)):
                        foreach ($modelos as $modelo): ?>
                            <option value="<?php echo esc_attr($modelo['Codigo']); ?>">
                                <?php echo esc_html($modelo['Nombre']); ?>
                            </option>
                    <?php endforeach;
                    endif; ?>
                </select>
            </div>

            <div>
                <label for="gnc">Usa GNC</label>
                <select type="text" name="gnc" id="gnc" for="gnc" required>
                    <option value="" disabled="">Usa GNC</option>
                    <option value="No">NO</option>
                    <option value="SI">SI</option>
                </select>
            </div>
        </div>

        <h3>Ubicacion</h3>
        <div class="form-line">
            <div>
                <label>Provincia</label>
                <select name="provincia" required>
                    <?php if (!empty($provincias)): ?>
                        <?php foreach ($provincias['Data'] as $prov): ?>
                            <option value="<?= esc_attr($prov['Value']) ?>|<?= esc_html($prov['Text']) ?>"><?= esc_html($prov['Text']) ?></option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="">No se pudieron cargar las provincias</option>
                    <?php endif; ?>
                </select>
            </div>

            <div>
                <label for="codigo_postal">Código Postal</label>
                <select name="codigo_postal" id="codigo_postal" for="codigo_postal" required style="width: 100%">
                    <option value="" disabled selected>Selecciona un codigo postal</option>
                    <?php foreach ($codigos_postales["Data"] as $codigo): ?>
                        <option value="<?= esc_attr($codigo['Value']) . " - " . $codigo['Text']; ?>">
                            <?= esc_html($codigo['Text']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <h3>Datos de la persona</h3>
        <div class="form-line">
            <div>

                <label for="tipo_doc">Tipo de Documento</label>

                <select name="tipo_doc" id="tipo_doc" for="tipo_doc" required="">
                    <option value="">Selecciona un tipo de documento</option>
                    <option value="Ext_CUIT80">C.U.I.T.</option>
                    <option value="Ext_CUIL86">CLAVE UNICA DE IDENTIFICACION LABORAL</option>
                    <option value="Ext_DNI96">DOCUMENTO NACIONAL IDENTIDAD</option>
                    <option value="Ext_LC90">L.C.</option>
                    <option value="Ext_LE89">L.E.</option>
                    <option value="Ext_PAS94">PASAPORTE</option>
                </select>
            </div>
            <div>
                <label for="nro_doc">Número de Documento</label>
                <input type="number" name="nro_doc" id="nro_doc" for="nro_doc" required="" value="">
            </div>
        </div>
        <div class="form-line">
            <div>
                <label>Teléfono Celular</label>

                <div class="phone-input-group">
                    <div class="phone-input-item telpre">
                        <label for="tel_prefijo">Cód. área (sin 0)</label>
                        <input type="number" name="tel_prefijo" id="tel_prefijo" required oninput="if(this.value.length > 4) this.value = this.value.slice(0,4);">
                    </div>
                    <div class="phone-input-item telnum">
                        <label for="tel_numero">Número</label>
                        <input type="number" name="tel_numero" id="tel_numero" required oninput="if(this.value.length > 8) this.value = this.value.slice(0,8);">
                    </div>
                </div>
            </div>
            <?php /* <div>
                <label for="estado_civil">Estado Civil</label>
                <select name="estado_civil" id="estado_civil" for="estado_civil" required="">
                    <option value="">Selecciona un estado civil</option>
                    <option value="01">SOLTERO</option>
                    <option value="02">CASADO</option>
                    <option value="03">DIVORCIADO</option>
                    <option value="04">VIUDO</option>
                    <option value="05">SEPARADO</option>
                </select>
            </div>*/ ?>
            <div class="col_sexo">
                <label for="sexo">Sexo</label>
                <select name="sexo" id="sexo" for="sexo" required="">
                    <option value="">Selecciona un sexo</option>
                    <option value="M">MASCULINO</option>
                    <option value="F">FEMENINO</option>
                    <option value="N">NO CORRESPONDE</option>
                </select>
            </div>
        </div>
        <div class="form-line">
            <div>


                <label for="fecha_nac">Fecha de nacimiento</label>
                <input type="text" name="fecha_nac" id="fecha_nac" for="fecha_nac" required="" value="">
            </div>


        </div>

        <button type="submit">Enviar y conocer resultado</button>
    </form>
</section>

<script>
    jQuery(document).ready(function($) {
        const provinciaSelect = document.querySelector('select[name="provincia"]');
        const cpSelect = $('#codigo_postal');

        // Inicializar Select2 en los campos
        $('#marca').select2({
            placeholder: "Seleccionar Marca",
        });
        $('#modelo').select2({
            placeholder: "Selecciona un modelo",
        });
        cpSelect.select2({
            placeholder: "Selecciona un código postal",
        });

        const marcaSelect = document.querySelector('#marca');
        const anioSelect = document.querySelector('#anio');
        const modeloSelect = document.querySelector('#modelo');
        const aniosDiv = document.querySelector('#div-anios');

        const condicion = document.querySelector('#condicion');

        if (condicion.value === "usado") {
            aniosDiv.classList.remove("hidden");
        } else {
            aniosDiv.classList.add("hidden");
            anioSelect.value = new Date().getFullYear();
        }

        // Al cargar la página: si ya hay marca y año, intentar cargar los modelos
        if (marcaSelect.value && anioSelect.value) {
            cargarModelosSiCorresponde();
        }

        // Añadir el event listener
        condicion.addEventListener('change', (e) => {
            // Obtener el valor del radio button seleccionado
            const selectedValue = e.target.value;

            if (selectedValue === "usado") {
                aniosDiv.classList.remove("hidden");
                cargarModelosSiCorresponde();
            } else {
                aniosDiv.classList.add("hidden");
                anioSelect.value = new Date().getFullYear();
                cargarModelosSiCorresponde();
            }


            if (marcaSelect == "") {
                modeloSelect.disabled = true;
            } else {
                modeloSelect.disabled = false;
            }

        });
        /////////////////////////////////////////////////////////////////

        provinciaSelect.addEventListener('change', function() {

            const provinciaId = this.value;

            // Deshabilitar y mostrar "Cargando..." en Select2
            cpSelect.empty().append('<option disabled selected>Cargando...</option>').trigger('change');
            cpSelect.prop("disabled", true);

            const apiToken = "<?= $token ?>";

            // Llamada a la API (reemplazá con tu endpoint real)
            fetch(`${miPluginData.rest_url}codigos-postales?provincia=${provinciaId}`)
                .then(res => res.json())
                .then(data => {
                    cpSelect.empty().append('<option value="" disabled selected>Selecciona un código postal</option>');

                    data.Data.forEach(codigo => {
                        const option = document.createElement('option');
                        const newValue = codigo.Value + " - " + codigo.Text;
                        option.value = newValue;
                        option.textContent = codigo.Text;
                        cpSelect.append(option);
                    });
                    cpSelect.prop("disabled", false);
                    cpSelect.trigger('change'); // Notificar a Select2 de los cambios
                })
                .catch(error => {
                    cpSelect.innerHTML = '<option disabled selected>Error al cargar</option>';
                    console.error('Error al obtener códigos postales:', error);
                });
        });

        // ********************************************* Logica get marcas *********************************************

        function cargarModelosSiCorresponde() {
            const marcaValue = marcaSelect.value;
            const anio = anioSelect.value;

            if (!marcaValue || !anio) {
                return;
            }

            const $modeloSelect = $(modeloSelect);
            // Limpiar opciones y mostrar "Cargando..." en el placeholder
            $modeloSelect.empty().trigger('change');
            $modeloSelect.select2({
                placeholder: "Cargando modelos..."
            });
            $modeloSelect.prop("disabled", true);

            const marcaId = marcaValue.split('|')[0];

            fetch(`${miPluginData.rest_url}modelos?marca=${marcaId}&anio=${condicion.value=='usado'?anio:'2025'}`)
                .then(res => res.json())
                .then(data => {
                    $modeloSelect.empty();
                    // Restaurar el placeholder original
                    $modeloSelect.select2({
                        placeholder: "Selecciona un modelo"
                    });
                    if (!Array.isArray(data.Data) || data.Data.length === 0) {
                        $modeloSelect.append('<option value="" disabled selected>No existen modelos para esta marca</option>');
                        $modeloSelect.removeAttr('required');
                    } else {
                        $modeloSelect.append('<option value="" disabled selected>Selecciona un modelo</option>');
                        data.Data.forEach(modelo => {
                            const option = new Option(modelo.Text, modelo.Value + '|' + modelo.Text);
                            $modeloSelect.append(option);
                        });
                        $modeloSelect.attr('required', 'required');
                    }
                    $modeloSelect.prop("disabled", false);
                    $modeloSelect.trigger('change'); // Notificar a Select2
                })
                .catch(error => {
                    modeloSelect.innerHTML = '<option disabled selected>Error al cargar modelos</option>';
                    console.error('Error al obtener modelos:', error);
                });
        }

        // Escuchamos cambios en ambos selects
        $('#marca').on('change', cargarModelosSiCorresponde);
        anioSelect.addEventListener('change', cargarModelosSiCorresponde);

        // Ejecutar al cargar si ya hay valores seleccionados
        if (marcaSelect.value) {
            if (!anioSelect.value) {
                anioSelect.value = "2025";
            }
            cargarModelosSiCorresponde();
        }

        const elem = document.getElementById('fecha_nac');
        const datepicker = new Datepicker(elem, {
            language: 'es',
            buttonClass: 'btn',
        });
    });


    function mostrarLoader(event) {
        // Evita que se envíe el formulario inmediatamente
        event.preventDefault();

        // Mostrar SweetAlert2 con loader y logo
        Swal.fire({
            // title: 'Enviando solicitud...',
            html: `
                <img src="<?php echo NORDEN_PLUGIN_URL; ?>assets/logos/LogoQuicksm.png" alt="Logo Quick Seguro" style="width: 150px; margin: .5rem;">
                <p style="font-family:"Gotham Book">Estamos procesando tu solicitud. Por favor, espera unos segundos.</p>
            `,
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Enviar el formulario después de mostrar el loader
        setTimeout(() => {
            event.target.submit();
        }, 1000);
    }
</script>