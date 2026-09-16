<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Questionnaire;
use App\Models\SurveyVersion;
use Illuminate\Database\Seeder;

class QuestionOptionSeeder extends Seeder
{
    public function run(): void
    {
        $questionnaire = Questionnaire::where(
            'name',
            'Encuesta de Situación Social'
        )->firstOrFail();

        $version = SurveyVersion::where(
            'questionnaire_id',
            $questionnaire->id
        )->where(
            'version',
            '1.0'
        )->firstOrFail();

        $questions = Question::whereHas('section', function ($query) use ($version) {
            $query->where('survey_version_id', $version->id);
        })->get()->keyBy('code');

        $options = [
            // ============================================================
            // GENERAL_004
            // ============================================================
            'GENERAL_004' => [
                ['value' => 'vivienda', 'label' => 'Vivienda'],
                ['value' => 'local_comercial', 'label' => 'Local comercial'],
                ['value' => 'vivienda_local_comercial', 'label' => 'Vivienda y local comercial'],
                ['value' => 'iglesia', 'label' => 'Iglesia'],
                ['value' => 'edificacion_construccion', 'label' => 'Edificación en construcción'],
                ['value' => 'centro_educativo_gubernamental', 'label' => 'Centro Educativo Gubernamental'],
                ['value' => 'centro_educativo_privado', 'label' => 'Centro Educativo Privado'],
                ['value' => 'centro_comunitario', 'label' => 'Centro Comunitario'],
                ['value' => 'salud_gubernamental', 'label' => 'Establecimiento de Salud Gubernamental'],
                ['value' => 'salud_privado', 'label' => 'Establecimiento de Salud Privado'],
                ['value' => 'infraestructura_deportiva', 'label' => 'Infraestructura deportiva'],
                ['value' => 'posta_policial_militar', 'label' => 'Posta policial/militar'],
                ['value' => 'parque_plaza_publica', 'label' => 'Parque o plaza pública'],
                ['value' => 'solar_baldio', 'label' => 'Solar baldío'],
                ['value' => 'no_identificado', 'label' => 'No identificado'],
                ['value' => 'otro', 'label' => 'Otro'],
            ],

            // ============================================================
            // GENERAL_005
            // ============================================================
            'GENERAL_005' => [
                ['value' => 'inundacion', 'label' => 'Inundación'],
                ['value' => 'deslizamiento_tierra', 'label' => 'Deslizamiento de tierra'],
                ['value' => 'huracan_lluvias_fuertes', 'label' => 'Huracán/lluvias fuertes'],
                ['value' => 'derrumbe', 'label' => 'Derrumbe'],
                ['value' => 'vientos_fuertes', 'label' => 'Vientos fuertes'],
                ['value' => 'socavon', 'label' => 'Socavón'],
                ['value' => 'incendio', 'label' => 'Incendio'],
                ['value' => 'caida_rocas', 'label' => 'Caída de rocas'],
                ['value' => 'otros', 'label' => 'Otros'],
                ['value' => 'ninguno', 'label' => 'NINGUNO'],
            ],

            // ============================================================
            // GENERAL_009
            // ============================================================
            'GENERAL_009' => [
                ['value' => 'sin_danos', 'label' => 'No se observan daños'],
                ['value' => 'danos_visibles', 'label' => 'Sí se observan daños'],
                ['value' => 'en_ruinas', 'label' => 'En ruinas'],
            ],

            // ============================================================
            // GENERAL_013
            // ============================================================
            'GENERAL_013' => [
                ['value' => 'hay_informante', 'label' => 'Sí, hay informante'],
                ['value' => 'informante_no_localizado', 'label' => 'No, informante no localizado'],
                ['value' => 'informante_se_nego', 'label' => 'Informante se negó a dar información'],
                ['value' => 'presenta_riesgo', 'label' => 'No, presenta riesgo'],
            ],

            // ============================================================
            // GENERAL_014
            // ============================================================
            'GENERAL_014' => [
                ['value' => 'ocupado', 'label' => 'Una fuente secundaria informa que está Ocupado'],
                ['value' => 'desocupado', 'label' => 'Una fuente secundaria informa que está Desocupado'],
                ['value' => 'no_identificado', 'label' => 'No identificado'],
            ],

            // ============================================================
            // HOUSING_001
            // ============================================================
            'HOUSING_001' => [
                ['value' => 'casa', 'label' => 'Casa'],
                ['value' => 'apartamento_piso', 'label' => 'Apartamento o piso'],
                ['value' => 'pieza_cuarteria', 'label' => 'Pieza en cuartería'],
                ['value' => 'refugio_improvisado', 'label' => 'Refugio improvisado'],
                ['value' => 'pieza_inquilinato', 'label' => 'Pieza de inquilinato en una casa'],
                ['value' => 'espacio_no_vivienda', 'label' => 'Espacio no construido para vivienda'],
                ['value' => 'otro', 'label' => 'Otro'],
            ],

            // ============================================================
            // HOUSING_003
            // ============================================================
            'HOUSING_003' => [
                ['value' => 'propia', 'label' => 'Propia'],
                ['value' => 'propia_pagandose', 'label' => 'Propia pagándose'],
                ['value' => 'alquilada', 'label' => 'Alquilada'],
                ['value' => 'cedida', 'label' => 'Cedida'],
                ['value' => 'herencia', 'label' => 'Herencia'],
                ['value' => 'ocupacion_irregular', 'label' => 'Ocupación irregular'],
                ['value' => 'ns_nr', 'label' => 'NS/NR'],
            ],

            // ============================================================
            // HOUSING_004
            // ============================================================
            'HOUSING_004' => [
                ['value' => 'dominio_pleno_amdc', 'label' => 'Dominio Pleno (otorgado por la AMDC)'],
                ['value' => 'titulo_propiedad_ip', 'label' => 'Titulo de propiedad del inmueble (otorgado por el IP)'],
                ['value' => 'titulo_supletorio', 'label' => 'Titulo supletorio (otorgado por el juzgado de letras del lo civil)'],
                ['value' => 'documento_compra_venta', 'label' => 'Documento de compra venta'],
                ['value' => 'ns_nr', 'label' => 'NS/NR'],
            ],

            // ============================================================
            // HOUSING_005
            // ============================================================
            'HOUSING_005' => [
                ['value' => 'promesa_venta', 'label' => 'Contrato de promesa venta'],
                ['value' => 'compra_venta_hipoteca', 'label' => 'Contrato de compra venta-hipoteca'],
                ['value' => 'otro', 'label' => 'Otro'],
                ['value' => 'ns_nr', 'label' => 'NS/NR'],
            ],

            // ============================================================
            // HOUSING_007
            // ============================================================
            'HOUSING_007' => [
                ['value' => 'dominio_util', 'label' => 'Dominio útil (otorgado por la AMDC, INA, particular)'],
                ['value' => 'usufructo', 'label' => 'Usufructo (otorgado por un particular)'],
                ['value' => 'sin_documento', 'label' => 'Sin documento (acuerdo verbal)'],
                ['value' => 'ns_nr', 'label' => 'NS/NR'],
            ],

            // ============================================================
            // HOUSING_008
            // ============================================================
            'HOUSING_008' => [
                ['value' => 'ab_intestato', 'label' => 'Se declaro heredero (AB intestato)'],
                ['value' => 'testamento', 'label' => 'Testamento'],
                ['value' => 'resolucion_declaracion_herencia', 'label' => 'Resolución de declaración de herencia (por el juzgado de letras de lo civil)'],
                ['value' => 'sin_proceso', 'label' => 'No hay proceso (sin documento)'],
                ['value' => 'ns_nr', 'label' => 'NS/NR'],
            ],

            // ============================================================
            // HOUSING_011
            // ============================================================
            'HOUSING_011' => [
                ['value' => 'ladrillo_bloque_piedra_cemento_concreto', 'label' => 'Ladrillo, bloque, piedra, cemento o concreto'],
                ['value' => 'adobe', 'label' => 'Adobe'],
                ['value' => 'madera', 'label' => 'Madera'],
                ['value' => 'embarro_bahareque', 'label' => 'Embarro o Bahareque'],
                ['value' => 'bambu_palma_palo_cana', 'label' => 'Bambú, palma, palo o caña'],
                ['value' => 'carton_comprimido_madera', 'label' => 'Láminas de cartón comprimido o madera'],
                ['value' => 'laminas_metalicas', 'label' => 'Láminas metálicas'],
                ['value' => 'laminas_desecho', 'label' => 'Paredes improvisadas con láminas de desecho'],
                ['value' => 'otro', 'label' => 'Otro'],
            ],

            // ============================================================
            // HOUSING_013 / 016 / 019
            // ============================================================
            'HOUSING_013' => [
                ['value' => 'buen_estado', 'label' => 'En buen estado'],
                ['value' => 'regular', 'label' => 'Regular'],
                ['value' => 'mal_estado', 'label' => 'En mal estado'],
            ],

            'HOUSING_014' => [
                ['value' => 'lamina_metalica', 'label' => 'Lámina metálica'],
                ['value' => 'lamina_asbesto', 'label' => 'Lámina de asbesto'],
                ['value' => 'teja_barro', 'label' => 'Teja de barro'],
                ['value' => 'losa_concreto_cemento', 'label' => 'Losa concreto/cemento'],
                ['value' => 'techo_desecho', 'label' => 'Techo improvisado con materiales de desecho'],
                ['value' => 'otro', 'label' => 'Otro'],
            ],

            'HOUSING_016' => [
                ['value' => 'buen_estado', 'label' => 'En buen estado'],
                ['value' => 'regular', 'label' => 'Regular'],
                ['value' => 'mal_estado', 'label' => 'En mal estado'],
            ],

            'HOUSING_017' => [
                ['value' => 'ceramica', 'label' => 'Cerámica'],
                ['value' => 'ladrillo_granito', 'label' => 'Ladrillo de granito'],
                ['value' => 'ladrillo_barro', 'label' => 'Ladrillo de barro'],
                ['value' => 'ladrillo_cemento', 'label' => 'Ladrillo de cemento'],
                ['value' => 'plancha_cemento', 'label' => 'Plancha de cemento'],
                ['value' => 'plastico_goma_tierra', 'label' => 'Láminas de plástico o goma sobre la tierra'],
                ['value' => 'tierra', 'label' => 'Tierra'],
                ['value' => 'otro', 'label' => 'Otro'],
            ],

            'HOUSING_019' => [
                ['value' => 'buen_estado', 'label' => 'En buen estado'],
                ['value' => 'regular', 'label' => 'Regular'],
                ['value' => 'mal_estado', 'label' => 'En mal estado'],
            ],

            // ============================================================
            // LOCAL
            // ============================================================
            'LOCAL_002' => [
                ['value' => 'femenino', 'label' => 'Femenino'],
                ['value' => 'masculino', 'label' => 'Masculino'],
            ],

            'LOCAL_004' => [
                ['value' => 'si', 'label' => 'Sí'],
                ['value' => 'no', 'label' => 'No'],
            ],

            'LOCAL_019' => [
                ['value' => 'primer_piso', 'label' => 'Primer piso'],
                ['value' => 'segundo_piso', 'label' => 'Segundo piso'],
            ],

            'LOCAL_020' => [
                ['value' => 'propio', 'label' => 'Propio'],
                ['value' => 'alquilado', 'label' => 'Alquilado'],
            ],

            // ============================================================
            // MEMBER_003 / MEMBER_008
            // ============================================================
            'MEMBER_003' => [
                ['value' => 'femenino', 'label' => 'Femenino'],
                ['value' => 'masculino', 'label' => 'Masculino'],
            ],

            'MEMBER_008' => [
                ['value' => 'si', 'label' => 'Sí'],
                ['value' => 'no', 'label' => 'No'],
            ],

            // ============================================================
            // MEMBER_009
            // ============================================================
            'MEMBER_009' => [
                ['value' => 'hospital_publico', 'label' => 'Hospital público'],
                ['value' => 'hospital_privado', 'label' => 'Hospital privado'],
                ['value' => 'centro_salud_publico', 'label' => 'Centro de salud público'],
                ['value' => 'centro_salud_privado', 'label' => 'Centro de salud privado'],
                ['value' => 'clinica_privada', 'label' => 'Clínica privada'],
                ['value' => 'otro', 'label' => 'Otro'],
                ['value' => 'ninguno', 'label' => 'Ninguno'],
            ],

            // ============================================================
            // MEMBER_011
            // ============================================================
            'MEMBER_011' => [
                ['value' => 'si', 'label' => 'Sí'],
                ['value' => 'no', 'label' => 'No'],
            ],

            'MEMBER_012' => [
                ['value' => 'fisica', 'label' => 'Física'],
                ['value' => 'visual', 'label' => 'Visual'],
                ['value' => 'auditiva', 'label' => 'Auditiva'],
                ['value' => 'intelectual', 'label' => 'Intelectual'],
                ['value' => 'psicosocial', 'label' => 'Psicosocial'],
                ['value' => 'multiple', 'label' => 'Múltiple'],
                ['value' => 'otra', 'label' => 'Otra'],
            ],

            // ============================================================
            // MEMBER_014 / MEMBER_015
            // ============================================================
            'MEMBER_014' => [
                ['value' => 'si', 'label' => 'Sí'],
                ['value' => 'no', 'label' => 'No'],
            ],

            'MEMBER_015' => [
                ['value' => 'diabetes', 'label' => 'Diabetes'],
                ['value' => 'hipertension', 'label' => 'Hipertensión'],
                ['value' => 'asma', 'label' => 'Asma'],
                ['value' => 'enfermedad_cardiaca', 'label' => 'Enfermedad cardíaca'],
                ['value' => 'enfermedad_renal', 'label' => 'Enfermedad renal'],
                ['value' => 'otra', 'label' => 'Otra'],
            ],

            // ============================================================
            // MEMBER_017 / MEMBER_018
            // ============================================================
            'MEMBER_017' => [
                ['value' => 'si', 'label' => 'Sí'],
                ['value' => 'no', 'label' => 'No'],
            ],

            'MEMBER_018' => [
                ['value' => 'si', 'label' => 'Sí'],
                ['value' => 'no', 'label' => 'No'],
            ],

            // ============================================================
            // MEMBER_019
            // ============================================================
            'MEMBER_019' => [
                ['value' => 'ninguno', 'label' => 'Ninguno'],
                ['value' => 'primer_grado', 'label' => 'Primer grado'],
                ['value' => 'segundo_grado', 'label' => 'Segundo grado'],
                ['value' => 'tercer_grado', 'label' => 'Tercer grado'],
                ['value' => 'cuarto_grado', 'label' => 'Cuarto grado'],
                ['value' => 'quinto_grado', 'label' => 'Quinto grado'],
                ['value' => 'sexto_grado', 'label' => 'Sexto grado'],
                ['value' => 'septimo_grado', 'label' => 'Séptimo grado'],
                ['value' => 'octavo_grado', 'label' => 'Octavo grado'],
                ['value' => 'noveno_grado', 'label' => 'Noveno grado'],
                ['value' => 'decimo_grado', 'label' => 'Décimo grado'],
                ['value' => 'undecimo_grado', 'label' => 'Undécimo grado'],
                ['value' => 'duodecimo_grado', 'label' => 'Duodécimo grado'],
                ['value' => 'tecnico', 'label' => 'Técnico'],
                ['value' => 'universidad_incompleta', 'label' => 'Universidad incompleta'],
                ['value' => 'universidad_completa', 'label' => 'Universidad completa'],
                ['value' => 'posgrado', 'label' => 'Posgrado'],
                ['value' => 'no_sabe_no_responde', 'label' => 'No sabe/no responde'],
            ],

            // ============================================================
            // MEMBER_020
            // ============================================================
            'MEMBER_020' => [
                ['value' => 'si', 'label' => 'Sí'],
                ['value' => 'no', 'label' => 'No'],
            ],
        ];

        foreach ($options as $questionCode => $questionOptions) {
            $question = $questions[$questionCode] ?? null;

            if (!$question) {
                continue;
            }

            foreach ($questionOptions as $index => $option) {
                QuestionOption::updateOrCreate(
                    [
                        'question_id' => $question->id,
                        'value' => $option['value'],
                    ],
                    [
                        'label' => $option['label'],
                        'sort_order' => $index + 1,
                        'active' => true,
                    ]
                );
            }
        }
    }
}
