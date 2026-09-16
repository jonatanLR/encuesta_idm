<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\QuestionType;
use App\Models\Section;
use App\Models\Questionnaire;
use App\Models\SurveyVersion;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
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

        $sections = Section::where(
            'survey_version_id',
            $version->id
        )->get()->keyBy('code');

        $types = QuestionType::all()->keyBy('code');

        $questions = [
            // ============================================================
            // I. INFORMACIÓN GENERAL DE LA ENCUESTA
            // ============================================================

            [
                'section' => 'GENERAL',
                'code' => 'GENERAL_001',
                'type' => 'date',
                'label' => 'Seleccione fecha de la encuesta',
                'required' => true,
                'sort_order' => 1,
            ],
            [
                'section' => 'GENERAL',
                'code' => 'GENERAL_002',
                'type' => 'text',
                'label' => 'Seleccione el nombre de la comunidad',
                'required' => true,
                'sort_order' => 2,
                'description' => 'Campo especial de búsqueda de comunidad. El valor seleccionado se almacena en SurveyResponse.community_id.',
            ],
            [
                'section' => 'GENERAL',
                'code' => 'GENERAL_003',
                'type' => 'text',
                'label' => 'En caso de que el nombre no aparezca en el listado. Por favor escríbalo aquí',
                'required' => false,
                'sort_order' => 3,
            ],
            [
                'section' => 'GENERAL',
                'code' => 'GENERAL_004',
                'type' => 'single_choice',
                'label' => '¿Qué tipo de inmueble se encuentra en el predio?',
                'required' => true,
                'sort_order' => 4,
            ],
            [
                'section' => 'GENERAL',
                'code' => 'GENERAL_005',
                'type' => 'single_choice',
                'label' => '¿Qué tipo de evento natural causó este daño en su vivienda?',
                'required' => true,
                'sort_order' => 5,
            ],
            [
                'section' => 'GENERAL',
                'code' => 'GENERAL_006',
                'type' => 'text',
                'label' => 'Especifique',
                'required' => false,
                'sort_order' => 6,
            ],
            [
                'section' => 'GENERAL',
                'code' => 'GENERAL_007',
                'type' => 'text',
                'label' => 'Escriba el nombre del inmueble',
                'required' => false,
                'sort_order' => 7,
            ],
            [
                'section' => 'GENERAL',
                'code' => 'GENERAL_008',
                'type' => 'text',
                'label' => '¿Qué tipo de local comercial es?',
                'required' => false,
                'sort_order' => 8,
            ],
            [
                'section' => 'GENERAL',
                'code' => 'GENERAL_009',
                'type' => 'single_choice',
                'label' => '¿El inmueble presenta daños visibles?',
                'required' => true,
                'sort_order' => 9,
            ],
            [
                'section' => 'GENERAL',
                'code' => 'GENERAL_010',
                'type' => 'image',
                'label' => 'Tome una fotografía clara del inmueble',
                'required' => false,
                'sort_order' => 10,
            ],
            [
                'section' => 'GENERAL',
                'code' => 'GENERAL_011',
                'type' => 'image',
                'label' => 'Tome una fotografía clara del inmueble — otro ángulo',
                'required' => false,
                'sort_order' => 11,
            ],
            [
                'section' => 'GENERAL',
                'code' => 'GENERAL_012',
                'type' => 'image',
                'label' => 'Tome una fotografía clara del inmueble — otros elementos relevantes',
                'required' => false,
                'sort_order' => 12,
            ],
            [
                'section' => 'GENERAL',
                'code' => 'GENERAL_013',
                'type' => 'single_choice',
                'label' => '¿Puede continuar con la encuesta?',
                'required' => true,
                'sort_order' => 13,
            ],
            [
                'section' => 'GENERAL',
                'code' => 'GENERAL_014',
                'type' => 'single_choice',
                'label' => 'Condición de ocupación del inmueble',
                'required' => true,
                'sort_order' => 14,
            ],

            // ============================================================
            // II. INFORMACIÓN DEL INFORMANTE
            // ============================================================

            [
                'section' => 'INFORMANT',
                'code' => 'INFORMANT_001',
                'type' => 'text',
                'label' => '¿Cuál es el nombre de la persona que está brindando la información?',
                'required' => true,
                'sort_order' => 1,
            ],
            [
                'section' => 'INFORMANT',
                'code' => 'INFORMANT_002',
                'type' => 'text',
                'label' => '¿Cuál es el número de teléfono de la persona que está brindando la información?',
                'required' => true,
                'sort_order' => 2,
            ],

            // ============================================================
            // III. INFORMACIÓN DE VIVIENDA Y HÁBITAT
            // ============================================================

            [
                'section' => 'HOUSING',
                'code' => 'HOUSING_001',
                'type' => 'single_choice',
                'label' => '¿Qué clase de vivienda particular es?',
                'required' => false,
                'sort_order' => 1,
            ],
            [
                'section' => 'HOUSING',
                'code' => 'HOUSING_002',
                'type' => 'text',
                'label' => 'Especifique que clase de vivienda particular es',
                'required' => false,
                'sort_order' => 2,
            ],
            [
                'section' => 'HOUSING',
                'code' => 'HOUSING_003',
                'type' => 'single_choice',
                'label' => 'La propiedad en dónde está construida esta vivienda es:',
                'required' => true,
                'sort_order' => 3,
            ],
            [
                'section' => 'HOUSING',
                'code' => 'HOUSING_004',
                'type' => 'single_choice',
                'label' => 'Tipo de documento de tenencia propia',
                'required' => false,
                'sort_order' => 4,
            ],
            [
                'section' => 'HOUSING',
                'code' => 'HOUSING_005',
                'type' => 'single_choice',
                'label' => 'Tipo de documento de tenencia propia pagándose',
                'required' => false,
                'sort_order' => 5,
            ],
            [
                'section' => 'HOUSING',
                'code' => 'HOUSING_006',
                'type' => 'text',
                'label' => 'Especifique que otro tipo de documento',
                'required' => false,
                'sort_order' => 6,
            ],
            [
                'section' => 'HOUSING',
                'code' => 'HOUSING_007',
                'type' => 'single_choice',
                'label' => 'Tipo de documento tenencia cedida',
                'required' => false,
                'sort_order' => 7,
            ],
            [
                'section' => 'HOUSING',
                'code' => 'HOUSING_008',
                'type' => 'single_choice',
                'label' => 'Tipo de documento tenencia herencia',
                'required' => false,
                'sort_order' => 8,
            ],
            [
                'section' => 'HOUSING',
                'code' => 'HOUSING_009',
                'type' => 'text',
                'label' => '¿Cuál es el nombre de la persona propietaria?',
                'required' => false,
                'sort_order' => 9,
            ],
            [
                'section' => 'HOUSING',
                'code' => 'HOUSING_010',
                'type' => 'text',
                'label' => 'Número de teléfono de la persona propietaria',
                'required' => false,
                'sort_order' => 10,
            ],
            [
                'section' => 'HOUSING',
                'code' => 'HOUSING_011',
                'type' => 'single_choice',
                'label' => '¿De qué material es la mayor parte de las paredes de esta vivienda?',
                'required' => false,
                'sort_order' => 11,
            ],
            [
                'section' => 'HOUSING',
                'code' => 'HOUSING_012',
                'type' => 'text',
                'label' => 'Especifique qué otro material',
                'required' => false,
                'sort_order' => 12,
            ],
            [
                'section' => 'HOUSING',
                'code' => 'HOUSING_013',
                'type' => 'single_choice',
                'label' => '¿En qué estado se encuentran las paredes de esta vivienda?',
                'required' => false,
                'sort_order' => 13,
            ],
            [
                'section' => 'HOUSING',
                'code' => 'HOUSING_014',
                'type' => 'single_choice',
                'label' => '¿De qué material es la mayor parte del techo de esta vivienda?',
                'required' => false,
                'sort_order' => 14,
            ],
            [
                'section' => 'HOUSING',
                'code' => 'HOUSING_015',
                'type' => 'text',
                'label' => 'Especifique qué otro material',
                'required' => false,
                'sort_order' => 15,
            ],
            [
                'section' => 'HOUSING',
                'code' => 'HOUSING_016',
                'type' => 'single_choice',
                'label' => '¿En qué estado se encuentra el techo de esta vivienda?',
                'required' => false,
                'sort_order' => 16,
            ],
            [
                'section' => 'HOUSING',
                'code' => 'HOUSING_017',
                'type' => 'single_choice',
                'label' => '¿De qué material es la mayor parte del piso de esta vivienda?',
                'required' => false,
                'sort_order' => 17,
            ],
            [
                'section' => 'HOUSING',
                'code' => 'HOUSING_018',
                'type' => 'text',
                'label' => 'Especifique qué otro material',
                'required' => false,
                'sort_order' => 18,
            ],
            [
                'section' => 'HOUSING',
                'code' => 'HOUSING_019',
                'type' => 'single_choice',
                'label' => '¿En qué estado se encuentra el piso de esta vivienda?',
                'required' => false,
                'sort_order' => 19,
            ],
            [
                'section' => 'HOUSING',
                'code' => 'HOUSING_020',
                'type' => 'number',
                'label' => '¿Cuántas piezas de esta vivienda se usan como dormitorio?',
                'required' => false,
                'sort_order' => 20,
            ],
            [
                'section' => 'HOUSING',
                'code' => 'HOUSING_021',
                'type' => 'text',
                'label' => 'Nombre completo de la persona encargada',
                'required' => false,
                'sort_order' => 21,
            ],
            [
                'section' => 'HOUSING',
                'code' => 'HOUSING_022',
                'type' => 'text',
                'label' => 'Número de teléfono de la persona encargada',
                'required' => false,
                'sort_order' => 22,
            ],

            // ============================================================
            // III.1 INFORMACIÓN DEL LOCAL
            // ============================================================

            [
                'section' => 'LOCAL',
                'code' => 'LOCAL_001',
                'type' => 'text',
                'label' => 'Nombre completo del propietario/a del negocio',
                'required' => false,
                'sort_order' => 1,
            ],
            [
                'section' => 'LOCAL',
                'code' => 'LOCAL_002',
                'type' => 'single_choice',
                'label' => 'Sexo',
                'required' => false,
                'sort_order' => 2,
            ],
            [
                'section' => 'LOCAL',
                'code' => 'LOCAL_003',
                'type' => 'decimal',
                'label' => 'Edad',
                'required' => false,
                'sort_order' => 3,
            ],
            [
                'section' => 'LOCAL',
                'code' => 'LOCAL_004',
                'type' => 'boolean',
                'label' => '¿Es madre/padre soltero?',
                'required' => false,
                'sort_order' => 4,
            ],
            [
                'section' => 'LOCAL',
                'code' => 'LOCAL_005',
                'type' => 'text',
                'label' => 'Escriba el DNI del propietario/a del negocio',
                'required' => false,
                'sort_order' => 5,
            ],
            [
                'section' => 'LOCAL',
                'code' => 'LOCAL_006',
                'type' => 'image',
                'label' => 'Foto frontal DNI',
                'required' => false,
                'sort_order' => 6,
            ],
            [
                'section' => 'LOCAL',
                'code' => 'LOCAL_007',
                'type' => 'image',
                'label' => 'Foto trasera DNI',
                'required' => false,
                'sort_order' => 7,
            ],
            [
                'section' => 'LOCAL',
                'code' => 'LOCAL_008',
                'type' => 'text',
                'label' => 'Número telefónico',
                'required' => false,
                'sort_order' => 8,
            ],
            [
                'section' => 'LOCAL',
                'code' => 'LOCAL_009',
                'type' => 'number',
                'label' => '¿Cuántas personas de su familia dependen económicamente de los ingresos de este negocio?',
                'required' => false,
                'sort_order' => 9,
            ],
            [
                'section' => 'LOCAL',
                'code' => 'LOCAL_010',
                'type' => 'number',
                'label' => 'De esas personas ¿Cuántas son Adulto Mayor (Mayor de 60 años)?',
                'required' => false,
                'sort_order' => 10,
            ],
            [
                'section' => 'LOCAL',
                'code' => 'LOCAL_011',
                'type' => 'number',
                'label' => '¿Cuántas tienen enfermedad crónica?',
                'required' => false,
                'sort_order' => 11,
            ],
            [
                'section' => 'LOCAL',
                'code' => 'LOCAL_012',
                'type' => 'number',
                'label' => '¿Cuántas personas tienen discapacidad?',
                'required' => false,
                'sort_order' => 12,
            ],
            [
                'section' => 'LOCAL',
                'code' => 'LOCAL_013',
                'type' => 'number',
                'label' => '¿Cuántas son Niñas(os) menores a 5 años?',
                'required' => false,
                'sort_order' => 13,
            ],
            [
                'section' => 'LOCAL',
                'code' => 'LOCAL_014',
                'type' => 'number',
                'label' => '¿Cuántas son Mujeres embarazadas?',
                'required' => false,
                'sort_order' => 14,
            ],
            [
                'section' => 'LOCAL',
                'code' => 'LOCAL_015',
                'type' => 'text',
                'label' => '¿Qué tipo de negocio tenía?',
                'required' => false,
                'sort_order' => 15,
            ],
            [
                'section' => 'LOCAL',
                'code' => 'LOCAL_016',
                'type' => 'number',
                'label' => '¿Cuántas personas tenía empleadas?',
                'required' => false,
                'sort_order' => 16,
            ],
            [
                'section' => 'LOCAL',
                'code' => 'LOCAL_017',
                'type' => 'number',
                'label' => '¿Cuántos locales ocupaba su negocio?',
                'required' => false,
                'sort_order' => 17,
            ],
            [
                'section' => 'LOCAL',
                'code' => 'LOCAL_018',
                'type' => 'text',
                'label' => '¿Cuál era el número de su local?',
                'required' => false,
                'sort_order' => 18,
            ],
            [
                'section' => 'LOCAL',
                'code' => 'LOCAL_019',
                'type' => 'single_choice',
                'label' => '¿En qué piso estaba ubicado el local?',
                'required' => false,
                'sort_order' => 19,
            ],
            [
                'section' => 'LOCAL',
                'code' => 'LOCAL_020',
                'type' => 'single_choice',
                'label' => '¿El local que usaba para su negocio era?',
                'required' => false,
                'sort_order' => 20,
            ],
            [
                'section' => 'LOCAL',
                'code' => 'LOCAL_021',
                'type' => 'decimal',
                'label' => '¿Aproximadamente cuánto capital tenía invertido?',
                'required' => false,
                'sort_order' => 21,
            ],
            [
                'section' => 'LOCAL',
                'code' => 'LOCAL_022',
                'type' => 'number',
                'label' => '¿Cantidad aproximada de estudiantes matriculados?',
                'required' => false,
                'sort_order' => 22,
            ],
            [
                'section' => 'LOCAL',
                'code' => 'LOCAL_023',
                'type' => 'number',
                'label' => '¿Cantidad de docentes/empleados?',
                'required' => false,
                'sort_order' => 23,
            ],
            [
                'section' => 'LOCAL',
                'code' => 'LOCAL_024',
                'type' => 'number',
                'label' => '¿Cantidad de empleados en este establecimiento de salud?',
                'required' => false,
                'sort_order' => 24,
            ],

            // ============================================================
            // IV. HOGAR
            // ============================================================

            [
                'section' => 'HOUSEHOLD',
                'code' => 'HOUSEHOLD_001',
                'type' => 'decimal',
                'label' => '¿Hace cuánto tiempo vive usted y su familia en esta vivienda?',
                'required' => false,
                'sort_order' => 1,
            ],

            // ============================================================
            // V. MIEMBRO - V.A DATOS GENERALES
            // ============================================================

            [
                'section' => 'MEMBER',
                'code' => 'MEMBER_001',
                'type' => 'text',
                'label' => 'Nombre completo',
                'required' => false,
                'sort_order' => 1,
            ],
            [
                'section' => 'MEMBER',
                'code' => 'MEMBER_002',
                'type' => 'decimal',
                'label' => 'Edad',
                'required' => false,
                'sort_order' => 2,
            ],
            [
                'section' => 'MEMBER',
                'code' => 'MEMBER_003',
                'type' => 'single_choice',
                'label' => 'Sexo',
                'required' => false,
                'sort_order' => 3,
            ],
            [
                'section' => 'MEMBER',
                'code' => 'MEMBER_004',
                'type' => 'single_choice',
                'label' => '¿Qué relación tiene con el jefe/a del hogar?',
                'required' => false,
                'sort_order' => 4,
            ],
            [
                'section' => 'MEMBER',
                'code' => 'MEMBER_005',
                'type' => 'text',
                'label' => 'Número de DNI',
                'required' => false,
                'sort_order' => 5,
            ],
            [
                'section' => 'MEMBER',
                'code' => 'MEMBER_006',
                'type' => 'image',
                'label' => 'Fotografía frontal del DNI',
                'required' => false,
                'sort_order' => 6,
            ],
            [
                'section' => 'MEMBER',
                'code' => 'MEMBER_007',
                'type' => 'image',
                'label' => 'Fotografía trasera del DNI',
                'required' => false,
                'sort_order' => 7,
            ],
            [
                'section' => 'MEMBER',
                'code' => 'MEMBER_008',
                'type' => 'single_choice',
                'label' => '¿Está embarazada?',
                'required' => false,
                'sort_order' => 8,
            ],

            // ============================================================
            // V.B SALUD
            // ============================================================

            [
                'section' => 'MEMBER',
                'code' => 'MEMBER_009',
                'type' => 'single_choice',
                'label' => '¿A dónde asiste cuando necesita atención médica?',
                'required' => false,
                'sort_order' => 9,
            ],
            [
                'section' => 'MEMBER',
                'code' => 'MEMBER_010',
                'type' => 'text',
                'label' => 'Especifique otro lugar de atención médica',
                'required' => false,
                'sort_order' => 10,
            ],
            [
                'section' => 'MEMBER',
                'code' => 'MEMBER_011',
                'type' => 'boolean',
                'label' => '¿Tiene alguna discapacidad?',
                'required' => false,
                'sort_order' => 11,
            ],
            [
                'section' => 'MEMBER',
                'code' => 'MEMBER_012',
                'type' => 'single_choice',
                'label' => '¿Qué tipo de discapacidad tiene?',
                'required' => false,
                'sort_order' => 12,
            ],
            [
                'section' => 'MEMBER',
                'code' => 'MEMBER_013',
                'type' => 'text',
                'label' => 'Especifique la discapacidad',
                'required' => false,
                'sort_order' => 13,
            ],
            [
                'section' => 'MEMBER',
                'code' => 'MEMBER_014',
                'type' => 'boolean',
                'label' => '¿Tiene alguna enfermedad crónica?',
                'required' => false,
                'sort_order' => 14,
            ],
            [
                'section' => 'MEMBER',
                'code' => 'MEMBER_015',
                'type' => 'single_choice',
                'label' => '¿Qué enfermedad crónica tiene?',
                'required' => false,
                'sort_order' => 15,
            ],
            [
                'section' => 'MEMBER',
                'code' => 'MEMBER_016',
                'type' => 'text',
                'label' => 'Especifique otra enfermedad crónica',
                'required' => false,
                'sort_order' => 16,
            ],

            // ============================================================
            // V.C EDUCACIÓN
            // ============================================================

            [
                'section' => 'MEMBER',
                'code' => 'MEMBER_017',
                'type' => 'boolean',
                'label' => '¿Sabe leer y escribir?',
                'required' => false,
                'sort_order' => 17,
            ],
            [
                'section' => 'MEMBER',
                'code' => 'MEMBER_018',
                'type' => 'boolean',
                'label' => '¿Actualmente está estudiando?',
                'required' => false,
                'sort_order' => 18,
            ],
            [
                'section' => 'MEMBER',
                'code' => 'MEMBER_019',
                'type' => 'single_choice',
                'label' => '¿Cuál fue el último grado/año que aprobó?',
                'required' => false,
                'sort_order' => 19,
            ],

            // ============================================================
            // V.D TRABAJO E INGRESOS
            // ============================================================

            [
                'section' => 'MEMBER',
                'code' => 'MEMBER_020',
                'type' => 'boolean',
                'label' => '¿La semana pasada trabajó por lo menos una hora para obtener ingresos o ayudar al negocio familiar?',
                'required' => false,
                'sort_order' => 20,
            ],
            [
                'section' => 'MEMBER',
                'code' => 'MEMBER_021',
                'type' => 'text',
                'label' => '¿Cuál es su ocupación?',
                'required' => false,
                'sort_order' => 21,
            ],
            [
                'section' => 'MEMBER',
                'code' => 'MEMBER_022',
                'type' => 'text',
                'label' => '¿Cuál es la modalidad de trabajo actual?',
                'required' => false,
                'sort_order' => 22,
            ],
            [
                'section' => 'MEMBER',
                'code' => 'MEMBER_023',
                'type' => 'decimal',
                'label' => '¿Cuánto gana aproximadamente al mes?',
                'required' => false,
                'sort_order' => 23,
            ],
            [
                'section' => 'MEMBER',
                'code' => 'MEMBER_024',
                'type' => 'text',
                'label' => '¿Cuánto tiempo lleva sin trabajo?',
                'required' => false,
                'sort_order' => 24,
            ],
            [
                'section' => 'MEMBER',
                'code' => 'MEMBER_025',
                'type' => 'text',
                'label' => 'Número de teléfono',
                'required' => false,
                'sort_order' => 25,
            ],

            // ============================================================
            // VI. CIERRE
            // ============================================================

            [
                'section' => 'CLOSURE',
                'code' => 'CLOSURE_001',
                'type' => 'textarea',
                'label' => 'Comentarios',
                'required' => false,
                'sort_order' => 1,
            ],
        ];

        foreach ($questions as $question) {
            Question::updateOrCreate(
                ['code' => $question['code']],
                [
                    'section_id' => $sections[$question['section']]->id,
                    'question_type_id' => $types[$question['type']]->id,
                    'label' => $question['label'],
                    'description' => $question['description'] ?? null,
                    'required' => $question['required'],
                    'active' => true,
                    'sort_order' => $question['sort_order'],
                ]
            );
        }

        Question::where('code', 'TEST_MULTIPLE_001')
            ->update(['active' => false]);
    }
}
