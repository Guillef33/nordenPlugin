<section class="container">
		<div class="form-header">
			<h1>Formulario de Cotización</h1>
			<p>Por favor, complete el siguiente formulario para cotizar su auto.</p>
		</div>

    <form method="POST" action="/cotizar-auto" class="form-cotizar">
        <div class="form-line">
            <h3>Datos del vehículo</h3>
            <div></div>

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

        <div class="form-line">
            <h3>Ubicacion</h3>
            <div></div>
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
                <label for="codigo_postal"></label> 
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

        <div class="form-line">
            <h3>Datos de la persona</h3>
            <div></div>
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