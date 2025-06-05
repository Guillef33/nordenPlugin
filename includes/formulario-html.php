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
                    <option value="" disabled selected>Seleccionar Marca</option>
                    <?php if (!empty($marcas)): ?>
                    <?php foreach ($marcas['Data'] as $marca): ?>
                    <option value="<?= esc_attr($marca['Value']) ?>"><?= esc_html($marca['Text']) ?></option>
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
                    <?php foreach ($modelos as $modelo): ?>
                        <option value="<?php echo esc_attr($modelo['Codigo']); ?>">
                            <?php echo esc_html($modelo['Nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="gnc">Usa GNC</label> 
                <select type="text" name="gnc"id="gnc" for="gnc" required>
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
                    <option value="<?= esc_attr($prov['Value']) ?>"><?= esc_html($prov['Text']) ?></option>
                <?php endforeach; ?>
                    <?php else: ?>
                        <option value="">No se pudieron cargar las provincias</option>
                    <?php endif; ?>
                </select>
            </div>

            <div>
                <label for="codigo_postal">Código Postal</label> 
                <select type="text" name="codigo_postal" id="codigo_postal" for="codigo_postal" searchable required>
                <option value="" disabled selected>Selecciona un codigo postal</option>
                    <?php foreach ($codigos_postales["Data"] as $codigo): ?>
                        <option value="<?= esc_attr($codigo['Value'])." - ".$codigo['Text']; ?>">
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

                <select  name="tipo_doc" id="tipo_doc" for="tipo_doc" required="">
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
                <label for="nro_doc">Numero de Documento</label> 
                <input type="text" name="nro_doc" id="nro_doc" for="nro_doc" required="" value="">
            </div>
            <div>
                <label for="estado_civil">Estado Civil</label> 
                <select name="estado_civil" id="estado_civil" for="estado_civil" required="">
                    <option value="">Selecciona un estado civil</option>
                    <option value="01">SOLTERO</option>
                    <option value="02">CASADO</option>
                    <option value="03">DIVORCIADO</option>
                    <option value="04">VIUDO</option>
                    <option value="05">SEPARADO</option>
                </select>
            </div>
            <div>
                <label for="sexo">Sexo</label>
                    <select name="sexo" id="sexo" for="sexo" required="">
                        <option value="">Selecciona un sexo</option>
                        <option value="M">MASCULINO</option>
                        <option value="F">FEMENINO</option>
                        <option value="N">NO CORRESPONDE</option>
                </select>
            </div>
            <div>

        
            <label for="fecha_nac">Fecha de nacimiento</label>
            <input type="date" name="fecha_nac" id="fecha_nac" for="fecha_nac" required="" value="">
            </div>

            
		</div>

        <button type="submit">Enviar y conocer resultado</button>
    </form>
    </section>

    <script>
document.addEventListener('DOMContentLoaded', function () {
  const provinciaSelect = document.querySelector('select[name="provincia"]');
  const cpSelect = document.querySelector('#codigo_postal');


  const marcaSelect = document.querySelector('#marca');
  const anioSelect = document.querySelector('#anio');
  const modeloSelect = document.querySelector('#modelo');
  const aniosDiv = document.querySelector('#div-anios');

const condicion = document.querySelector('#condicion');


if (condicion.value === "usado") {
  aniosDiv.classList.remove("hidden");
} else {
  aniosDiv.classList.add("hidden");
  anioSelect.value= new Date().getFullYear();
}

// Al cargar la página: si ya hay marca y año, intentar cargar los modelos
if (marcaSelect.value && anioSelect.value) {
  cargarModelosSiCorresponde();
}

// Añadir el event listener
condicion.addEventListener('change', (e) => {
  // Obtener el valor del radio button seleccionado
  const selectedValue = e.target.value;

  if(selectedValue==="usado"){
    aniosDiv.classList.remove("hidden");
    cargarModelosSiCorresponde();
}else{
    aniosDiv.classList.add("hidden");
    anioSelect.value= new Date().getFullYear();
    cargarModelosSiCorresponde();
  }

  if(marcaSelect==""){
    modeloSelect.disabled=true;
  }else{
    modeloSelect.disabled=false;
  }
  
});
  /////////////////////////////////////////////////////////////////

  provinciaSelect.addEventListener('change', function () {
    const provinciaId = this.value;

    // Limpiar opciones anteriores
    cpSelect.innerHTML = '<option disabled selected>Cargando...</option>';

    const apiToken = "<?= $token ?>";

    // Llamada a la API (reemplazá con tu endpoint real)
    fetch(`${miPluginData.rest_url}codigos-postales?provincia=${provinciaId}`)
      .then(res => res.json())
      .then(data => {
        cpSelect.innerHTML = '<option disabled selected>Selecciona un código postal</option>';

        data.Data.forEach(codigo => {
          const option = document.createElement('option');
          const newValue=codigo.Value+" - "+codigo.Text;
          option.value = newValue;
          option.textContent = codigo.Text;
          cpSelect.appendChild(option);
        });
      })
      .catch(error => {
        cpSelect.innerHTML = '<option disabled selected>Error al cargar</option>';
        console.error('Error al obtener códigos postales:', error);
      });
  });
  
  // ********************************************* Logica get marcas *********************************************
  
  function cargarModelosSiCorresponde() {
      const marcaId = marcaSelect.value;
      const anio = anioSelect.value;
      
      if (!marcaId || !anio) {
          return;
        }
        
        modeloSelect.innerHTML = '<option disabled selected>Cargando modelos...</option>';
        
        fetch(`${miPluginData.rest_url}modelos?marca=${marcaId}&anio=${anio}`)
        .then(res => res.json())
        .then(data => {
            modeloSelect.innerHTML = '<option disabled selected>Selecciona un modelo</option>';
            data.Data.forEach(modelo => {
                const option = document.createElement('option');
                option.value = modelo.Value;
                option.textContent = modelo.Text;
                modeloSelect.appendChild(option);
            });
        })
        .catch(error => {
            modeloSelect.innerHTML = '<option disabled selected>Error al cargar modelos</option>';
            console.error('Error al obtener modelos:', error);
        });
    }
    
    // Escuchamos cambios en ambos selects
    marcaSelect.addEventListener('change', cargarModelosSiCorresponde);
    anioSelect.addEventListener('change', cargarModelosSiCorresponde);
});
    
</script>

<script>
  // Función principal para mostrar loader con validación previa
        function mostrarLoader(event) {
            // Evita que se envíe el formulario inmediatamente
            event.preventDefault();
            
            // Validar formulario antes de mostrar el loader
            const errores = validarFormulario();
            
            if (errores.length > 0) {
                // Mostrar errores con Sweet Alert
                mostrarErrores(errores);
                return;
            }
            
            // Si no hay errores, mostrar loader y enviar
            mostrarLoaderYEnviar(event);
        }

        // Función para validar todos los campos del formulario
        function validarFormulario() {
            const errores = [];
            
            // Validar condición del vehículo
            const condicion = document.getElementById('condicion').value;
            if (!condicion) {
                errores.push('La condición del vehículo es requerida');
            }
            
            // Validar año (solo si es usado)
            const anio = document.getElementById('anio').value;
            const aniosDiv = document.getElementById('div-anios');
            if (condicion === 'usado' && !aniosDiv.classList.contains('hidden')) {
                if (!anio) {
                    errores.push('El año del vehículo es requerido');
                } else {
                    const anioNum = parseInt(anio);
                    const anioActual = new Date().getFullYear();
                    if (anioNum < 1900 || anioNum > (anioActual + 1)) {
                        errores.push(`El año del vehículo debe estar entre 1900 y ${anioActual + 1}`);
                    }
                }
            }
            
            // Validar marca
            const marca = document.getElementById('marca').value;
            if (!marca) {
                errores.push('La marca del vehículo es requerida');
            }
            
            // Validar modelo
            const modelo = document.getElementById('modelo').value;
            if (!modelo) {
                errores.push('El modelo del vehículo es requerido');
            }
            
            // Validar GNC
            const gnc = document.getElementById('gnc').value;
            if (!gnc) {
                errores.push('Debe especificar si usa GNC');
            }
            
            // Validar provincia
            const provincia = document.getElementById('provincia').value;
            if (!provincia) {
                errores.push('La provincia es requerida');
            }
            
            // Validar código postal
            const codigoPostal = document.getElementById('codigo_postal').value;
            if (!codigoPostal) {
                errores.push('El código postal es requerido');
            } else {
                // Validar formato del código postal
                const arr = codigoPostal.split(' - ');
                if (arr.length < 3) {
                    errores.push('El formato del código postal es incorrecto');
                } else {
                    const intId = arr[0].trim();
                    if (!intId || !isNumeric(intId)) {
                        errores.push('El ID de localidad no es válido');
                    }
                }
            }
            
            // Validar tipo de documento
            const tipoDoc = document.getElementById('tipo_doc').value;
            if (!tipoDoc) {
                errores.push('El tipo de documento es requerido');
            }
            
            // Validar número de documento
            const nroDoc = document.getElementById('nro_doc').value;
            if (!nroDoc) {
                errores.push('El número de documento es requerido');
            } else if (!isNumeric(nroDoc)) {
                errores.push('El número de documento debe ser numérico');
            } else if (nroDoc.length < 7 || nroDoc.length > 8) {
                errores.push('El número de documento debe tener entre 7 y 8 dígitos');
            }
            
            // Validar estado civil
            const estadoCivil = document.getElementById('estado_civil').value;
            if (!estadoCivil) {
                errores.push('El estado civil es requerido');
            }
            
            // Validar sexo
            const sexo = document.getElementById('sexo').value;
            if (!sexo) {
                errores.push('El sexo es requerido');
            }
            
            // Validar fecha de nacimiento
            const fechaNac = document.getElementById('fecha_nac').value;
            if (!fechaNac) {
                errores.push('La fecha de nacimiento es requerida');
            } else {
                // Validar formato de fecha
                const fechaRegex = /^\d{4}-\d{2}-\d{2}$/;
                if (!fechaRegex.test(fechaNac)) {
                    errores.push('El formato de fecha de nacimiento es incorrecto');
                } else {
                    // Validar que la fecha sea válida y no sea futura
                    const fecha = new Date(fechaNac);
                    const hoy = new Date();
                    if (fecha > hoy) {
                        errores.push('La fecha de nacimiento no puede ser futura');
                    }
                    
                    // Validar edad mínima (18 años)
                    const edad = calcularEdad(fecha);
                    if (edad < 18) {
                        errores.push('Debe ser mayor de 18 años para contratar un seguro');
                    }
                }
            }
            
            return errores;
        }

        // Función para mostrar errores con Sweet Alert
        function mostrarErrores(errores) {
            let listaErrores = '<ul style="text-align: left; padding-left: 20px;">';
            errores.forEach(error => {
                listaErrores += `<li style="margin-bottom: 8px; line-height: 1.4;">${error}</li>`;
            });
            listaErrores += '</ul>';
            
            Swal.fire({
                icon: 'error',
                title: 'Por favor, corrige los siguientes errores:',
                html: listaErrores,
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#d33',
                width: '600px',
                customClass: {
                    popup: 'error-popup'
                }
            });
        }

        // Función para mostrar loader y enviar formulario
        function mostrarLoaderYEnviar(event) {
            Swal.fire({
                title: 'Enviando solicitud...',
                html: `
                    <img src="https://chocolate-hyena-849814.hostingersite.com/wp-content/uploads/2025/03/LogoQuick.png" 
                         alt="Logo Banco" 
                         style="width: 100px; margin-bottom: 1rem;">
                    <p>Estamos procesando tu solicitud. Por favor, espera unos segundos.</p>
                    <div class="progress-bar-container" style="width: 100%; background-color: #f0f0f0; border-radius: 10px; margin-top: 20px;">
                        <div class="progress-bar" style="width: 0%; height: 20px; background-color: #007bff; border-radius: 10px; transition: width 0.3s ease;"></div>
                    </div>
                `,
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                    
                    // Simular progreso de la barra
                    const progressBar = document.querySelector('.progress-bar');
                    let width = 0;
                    const interval = setInterval(() => {
                        width += 10;
                        if (progressBar) {
                            progressBar.style.width = width + '%';
                        }
                        if (width >= 90) {
                            clearInterval(interval);
                        }
                    }, 100);
                }
            });

            // Enviar el formulario después de mostrar el loader
            setTimeout(() => {
                event.target.submit();
            }, 1000);
        }

        // Funciones auxiliares
        function isNumeric(str) {
            return /^\d+$/.test(str);
        }

        function calcularEdad(fechaNacimiento) {
            const hoy = new Date();
            const nacimiento = new Date(fechaNacimiento);
            let edad = hoy.getFullYear() - nacimiento.getFullYear();
            const mes = hoy.getMonth() - nacimiento.getMonth();
            
            if (mes < 0 || (mes === 0 && hoy.getDate() < nacimiento.getDate())) {
                edad--;
            }
        }
</script>
