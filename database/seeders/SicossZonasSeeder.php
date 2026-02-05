<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SicossZona;

class SicossZonasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $zonas = [
            ['codigo'=>1, 'numero'=>'02', 'localidad'=>'Buenos Aires - Almte. Brown'],
            ['codigo'=>2, 'numero'=>'02', 'localidad'=>'Buenos Aires - Avellaneda'],
            ['codigo'=>3, 'numero'=>'02', 'localidad'=>'Buenos Aires - Berazategui'],
            ['codigo'=>4, 'numero'=>'03', 'localidad'=>'Buenos Aires - Berisso'],
            ['codigo'=>5, 'numero'=>'03', 'localidad'=>'Buenos Aires - Cañuelas'],
            ['codigo'=>6, 'numero'=>'04', 'localidad'=>'Buenos Aires - Carmen de Patagones'],
            ['codigo'=>7, 'numero'=>'03', 'localidad'=>'Buenos Aires - Ensenada'],
            ['codigo'=>8, 'numero'=>'03', 'localidad'=>'Buenos Aires - Escobar'],
            ['codigo'=>9, 'numero'=>'02', 'localidad'=>'Buenos Aires - Esteban Echeverría'],
            ['codigo'=>10, 'numero'=>'02', 'localidad'=>'Buenos Aires - Florencio Varela'],
            ['codigo'=>11, 'numero'=>'03', 'localidad'=>'Buenos Aires - General Rodríguez'],
            ['codigo'=>12, 'numero'=>'02', 'localidad'=>'Buenos Aires - General San Martín'],
            ['codigo'=>13, 'numero'=>'02', 'localidad'=>'Buenos Aires - General Sarmiento'],
            ['codigo'=>14, 'numero'=>'02', 'localidad'=>'Gran Buenos Aires'],
            ['codigo'=>15, 'numero'=>'02', 'localidad'=>'Buenos Aires - La Matanza'],
            ['codigo'=>16, 'numero'=>'03', 'localidad'=>'Buenos Aires - La Plata'],
            ['codigo'=>17, 'numero'=>'02', 'localidad'=>'Buenos Aires - Lanús'],
            ['codigo'=>18, 'numero'=>'02', 'localidad'=>'Buenos Aires - Lomas de Zamora'],
            ['codigo'=>19, 'numero'=>'03', 'localidad'=>'Buenos Aires - Marcos Paz'],
            ['codigo'=>20, 'numero'=>'02', 'localidad'=>'Buenos Aires - Merlo'],
            ['codigo'=>21, 'numero'=>'02', 'localidad'=>'Buenos Aires - Moreno'],
            ['codigo'=>22, 'numero'=>'02', 'localidad'=>'Buenos Aires - Morón'],
            ['codigo'=>23, 'numero'=>'05', 'localidad'=>'Buenos Aires - Patagones'],
            ['codigo'=>24, 'numero'=>'03', 'localidad'=>'Buenos Aires - Pilar'],
            ['codigo'=>25, 'numero'=>'02', 'localidad'=>'Buenos Aires - Quilmes'],
            ['codigo'=>26, 'numero'=>'07', 'localidad'=>'Buenos Aires - Resto de la Provincia'],
            ['codigo'=>27, 'numero'=>'02', 'localidad'=>'Buenos Aires - San Fernando'],
            ['codigo'=>28, 'numero'=>'02', 'localidad'=>'Buenos Aires - San Isidro'],
            ['codigo'=>29, 'numero'=>'03', 'localidad'=>'Buenos Aires - San Vicente'],
            ['codigo'=>30, 'numero'=>'02', 'localidad'=>'Buenos Aires - Tigre'],
            ['codigo'=>31, 'numero'=>'02', 'localidad'=>'Buenos Aires - Tres de Febrero'],
            ['codigo'=>32, 'numero'=>'02', 'localidad'=>'Buenos Aires - Vicente López'],
            ['codigo'=>33, 'numero'=>'06', 'localidad'=>'Buenos Aires - Villarino'],
            ['codigo'=>34, 'numero'=>'01', 'localidad'=>'Capital Federal'],
            ['codigo'=>35, 'numero'=>'09', 'localidad'=>'Catamarca'],
            ['codigo'=>36, 'numero'=>'D5', 'localidad'=>'Catamarca - Antofagasta de la Sierra (Actividad Minera)'],
            ['codigo'=>37, 'numero'=>'D6', 'localidad'=>'Catamarca - Antofagasta de la Sierra (Resto Actividades)'],
            ['codigo'=>38, 'numero'=>'08', 'localidad'=>'Catamarca - Gran Catamarca'],
            ['codigo'=>39, 'numero'=>'27', 'localidad'=>'Chaco'],
            ['codigo'=>40, 'numero'=>'26', 'localidad'=>'Chaco - Gran Resistencia'],
            ['codigo'=>41, 'numero'=>'29', 'localidad'=>'Chubut'],
            ['codigo'=>42, 'numero'=>'28', 'localidad'=>'Chubut - Rawson'],
            ['codigo'=>43, 'numero'=>'28', 'localidad'=>'Chubut - Trelew'],
            ['codigo'=>44, 'numero'=>'13', 'localidad'=>'Córdoba - Cruz del Eje'],
            ['codigo'=>45, 'numero'=>'18', 'localidad'=>'Córdoba - Gran Córdoba'],
            ['codigo'=>46, 'numero'=>'14', 'localidad'=>'Córdoba - Minas'],
            ['codigo'=>47, 'numero'=>'15', 'localidad'=>'Córdoba - Pocho'],
            ['codigo'=>48, 'numero'=>'19', 'localidad'=>'Córdoba - Resto de la Provincia'],
            ['codigo'=>49, 'numero'=>'11', 'localidad'=>'Córdoba - Río Seco'],
            ['codigo'=>50, 'numero'=>'16', 'localidad'=>'Córdoba - San Alberto'],
            ['codigo'=>51, 'numero'=>'17', 'localidad'=>'Córdoba - San Javier'],
            ['codigo'=>52, 'numero'=>'10', 'localidad'=>'Córdoba - Sobremonte'],
            ['codigo'=>53, 'numero'=>'12', 'localidad'=>'Córdoba - Tulumba'],

            ['codigo'=>54, 'numero'=>'24', 'localidad'=>'Corrientes - Ciudad de Corrientes'],
            ['codigo'=>55, 'numero'=>'22', 'localidad'=>'Corrientes - Curuzú Cuatiá'],
            ['codigo'=>56, 'numero'=>'20', 'localidad'=>'Corrientes - Esquina'],
            ['codigo'=>57, 'numero'=>'23', 'localidad'=>'Corrientes - Monte Caseros'],
            ['codigo'=>58, 'numero'=>'25', 'localidad'=>'Corrientes - Resto de la Provincia'],
            ['codigo'=>59, 'numero'=>'21', 'localidad'=>'Corrientes - Sauce'],

            ['codigo'=>60, 'numero'=>'30', 'localidad'=>'Entre Ríos - Federación'],
            ['codigo'=>61, 'numero'=>'31', 'localidad'=>'Entre Ríos - Feliciano'],
            ['codigo'=>62, 'numero'=>'32', 'localidad'=>'Entre Ríos - Paraná'],
            ['codigo'=>63, 'numero'=>'33', 'localidad'=>'Entre Ríos - Resto de la Provincia'],

            ['codigo'=>64, 'numero'=>'35', 'localidad'=>'Formosa'],
            ['codigo'=>65, 'numero'=>'87', 'localidad'=>'Formosa - Bermejo'],
            ['codigo'=>66, 'numero'=>'34', 'localidad'=>'Formosa - Ciudad de Formosa'],
            ['codigo'=>67, 'numero'=>'89', 'localidad'=>'Formosa - Mataco'],
            ['codigo'=>68, 'numero'=>'88', 'localidad'=>'Formosa - Ramón Lista'],
            ['codigo'=>69, 'numero'=>'86', 'localidad'=>'Identifica trabajador siniestrado hasta V 9.3'],

            ['codigo'=>70, 'numero'=>'37', 'localidad'=>'Jujuy'],
            ['codigo'=>71, 'numero'=>'36', 'localidad'=>'Jujuy - Ciudad de Jujuy'],
            ['codigo'=>72, 'numero'=>'D7', 'localidad'=>'Jujuy - Cochinoca'],
            ['codigo'=>73, 'numero'=>'D8', 'localidad'=>'Jujuy - Humahuaca'],
            ['codigo'=>74, 'numero'=>'D9', 'localidad'=>'Jujuy - Rinconada'],
            ['codigo'=>75, 'numero'=>'E0', 'localidad'=>'Jujuy - Santa Catalina'],
            ['codigo'=>76, 'numero'=>'E1', 'localidad'=>'Jujuy - Susques'],
            ['codigo'=>77, 'numero'=>'E2', 'localidad'=>'Jujuy - Yavi'],

            ['codigo'=>78, 'numero'=>'39', 'localidad'=>'La Pampa - Chalileo'],
            ['codigo'=>79, 'numero'=>'38', 'localidad'=>'La Pampa - Chical Co'],
            ['codigo'=>80, 'numero'=>'42', 'localidad'=>'La Pampa - Curacó'],
            ['codigo'=>81, 'numero'=>'43', 'localidad'=>'La Pampa - Lihuel Calel'],
            ['codigo'=>82, 'numero'=>'41', 'localidad'=>'La Pampa - Limay Mahuida'],
            ['codigo'=>83, 'numero'=>'40', 'localidad'=>'La Pampa - Puelén'],
            ['codigo'=>84, 'numero'=>'45', 'localidad'=>'La Pampa - Resto de la Provincia'],
            ['codigo'=>85, 'numero'=>'44', 'localidad'=>'La Pampa - Santa Rosa'],
            ['codigo'=>86, 'numero'=>'44', 'localidad'=>'La Pampa - Toay'],

            ['codigo'=>87, 'numero'=>'47', 'localidad'=>'La Rioja'],
            ['codigo'=>88, 'numero'=>'46', 'localidad'=>'La Rioja - Ciudad de La Rioja'],

            ['codigo'=>89, 'numero'=>'49', 'localidad'=>'Mendoza'],
            ['codigo'=>90, 'numero'=>'A9', 'localidad'=>'Mendoza - Resto Distritos San Carlos'],
            ['codigo'=>91, 'numero'=>'A6', 'localidad'=>'Mendoza - Tunuyán - Campos de los Andes'],
            ['codigo'=>92, 'numero'=>'48', 'localidad'=>'Mendoza - Gran Mendoza'],
            ['codigo'=>93, 'numero'=>'90', 'localidad'=>'Mendoza - Las Heras - Las Cuevas'],
            ['codigo'=>94, 'numero'=>'94', 'localidad'=>'Mendoza - Luján de Cuyo - Agrelo'],
            ['codigo'=>95, 'numero'=>'93', 'localidad'=>'Mendoza - Luján de Cuyo - Carrizal'],
            ['codigo'=>96, 'numero'=>'97', 'localidad'=>'Mendoza - Luján de Cuyo - Las Compuertas'],
            ['codigo'=>97, 'numero'=>'96', 'localidad'=>'Mendoza - Luján de Cuyo - Perdriel'],
            ['codigo'=>98, 'numero'=>'92', 'localidad'=>'Mendoza - Luján de Cuyo - Potrerillos'],
            ['codigo'=>99, 'numero'=>'95', 'localidad'=>'Mendoza - Luján de Cuyo - Ugarteche'],

            ['codigo'=>100, 'numero'=>'B8', 'localidad'=>'Mendoza - Maipú - Cruz de Piedra'],
            ['codigo'=>101, 'numero'=>'C0', 'localidad'=>'Mendoza - Maipú - Las Barrancas'],
            ['codigo'=>102, 'numero'=>'B9', 'localidad'=>'Mendoza - Maipú - Lumlunta'],
            ['codigo'=>103, 'numero'=>'B7', 'localidad'=>'Mendoza - Maipú - Russell'],
            ['codigo'=>104, 'numero'=>'B3', 'localidad'=>'Mendoza - Malargüe - Río Grande'],
            ['codigo'=>105, 'numero'=>'B5', 'localidad'=>'Mendoza - Malargüe - Agua Escondida'],
            ['codigo'=>106, 'numero'=>'B2', 'localidad'=>'Mendoza - Malargüe - Malargüe'],
            ['codigo'=>107, 'numero'=>'B4', 'localidad'=>'Mendoza - Malargüe - Río Barrancas'],
            ['codigo'=>108, 'numero'=>'98', 'localidad'=>'Mendoza - Resto Distritos Luján de Cuyo'],
            ['codigo'=>109, 'numero'=>'C1', 'localidad'=>'Mendoza - Resto Distritos Maipú'],
            ['codigo'=>110, 'numero'=>'B6', 'localidad'=>'Mendoza - Resto Distritos Malargüe'],
            ['codigo'=>111, 'numero'=>'C7', 'localidad'=>'Mendoza - Resto Distritos Rivadavia'],
            ['codigo'=>112, 'numero'=>'B1', 'localidad'=>'Mendoza - Resto Distritos San Rafael'],
            ['codigo'=>113, 'numero'=>'A7', 'localidad'=>'Mendoza - Resto Distritos Tunuyán'],
            ['codigo'=>114, 'numero'=>'A3', 'localidad'=>'Mendoza - Resto Distritos Tupungato'],
            ['codigo'=>115, 'numero'=>'91', 'localidad'=>'Mendoza - Resto Las Heras'],

            ['codigo'=>116, 'numero'=>'C2', 'localidad'=>'Mendoza - Rivadavia - El Mirador'],
            ['codigo'=>117, 'numero'=>'C4', 'localidad'=>'Mendoza - Rivadavia - Los Árboles'],
            ['codigo'=>118, 'numero'=>'C3', 'localidad'=>'Mendoza - Rivadavia - Los Campamentos'],
            ['codigo'=>119, 'numero'=>'C6', 'localidad'=>'Mendoza - Rivadavia - Medrano'],
            ['codigo'=>120, 'numero'=>'C5', 'localidad'=>'Mendoza - Rivadavia - Reducción'],

            ['codigo'=>121, 'numero'=>'A8', 'localidad'=>'Mendoza - San Carlos - Pareditas'],
            ['codigo'=>122, 'numero'=>'B0', 'localidad'=>'Mendoza - San Rafael - Cuadro Venegas'],
            ['codigo'=>123, 'numero'=>'A4', 'localidad'=>'Mendoza - Tunuyán - Los Árboles'],
            ['codigo'=>124, 'numero'=>'A5', 'localidad'=>'Mendoza - Tunuyán - Los Chacayes'],
            ['codigo'=>125, 'numero'=>'A2', 'localidad'=>'Mendoza - Tupungato - Anchoris'],
            ['codigo'=>126, 'numero'=>'99', 'localidad'=>'Mendoza - Tupungato - Santa Clara'],
            ['codigo'=>127, 'numero'=>'A0', 'localidad'=>'Mendoza - Tupungato - Zapata'],
            ['codigo'=>128, 'numero'=>'A1', 'localidad'=>'Mendoza - Tupungato - San José'],

            ['codigo'=>129, 'numero'=>'51', 'localidad'=>'Misiones'],
            ['codigo'=>130, 'numero'=>'50', 'localidad'=>'Misiones - Posadas'],

            ['codigo'=>131, 'numero'=>'56', 'localidad'=>'Neuquén'],
            ['codigo'=>132, 'numero'=>'53', 'localidad'=>'Neuquén - Centenario'],
            ['codigo'=>133, 'numero'=>'52', 'localidad'=>'Neuquén - Ciudad de Neuquén'],
            ['codigo'=>134, 'numero'=>'54', 'localidad'=>'Neuquén - Cutral-Có'],
            ['codigo'=>135, 'numero'=>'55', 'localidad'=>'Neuquén - Plaza Huincul'],
            ['codigo'=>136, 'numero'=>'52', 'localidad'=>'Neuquén - Plottier'],

            ['codigo'=>137, 'numero'=>'59', 'localidad'=>'Río Negro - Alejandro Stefenelli'],
            ['codigo'=>138, 'numero'=>'59', 'localidad'=>'Río Negro - Allen'],
            ['codigo'=>139, 'numero'=>'59', 'localidad'=>'Río Negro - Alto Valle'],
            ['codigo'=>140, 'numero'=>'59', 'localidad'=>'Río Negro - Cervantes'],
            ['codigo'=>141, 'numero'=>'59', 'localidad'=>'Río Negro - Chichinales'],
            ['codigo'=>142, 'numero'=>'59', 'localidad'=>'Río Negro - Cinco Saltos'],
            ['codigo'=>143, 'numero'=>'59', 'localidad'=>'Río Negro - Cipolletti'],
            ['codigo'=>144, 'numero'=>'59', 'localidad'=>'Río Negro - Contralmirante Cordero'],
            ['codigo'=>145, 'numero'=>'59', 'localidad'=>'Río Negro - Coronel Juan J. Gómez'],
            ['codigo'=>146, 'numero'=>'59', 'localidad'=>'Río Negro - Fernández Oro'],
            ['codigo'=>147, 'numero'=>'59', 'localidad'=>'Río Negro - Gral. Enrique Godoy'],
            ['codigo'=>148, 'numero'=>'59', 'localidad'=>'Río Negro - General Roca'],
            ['codigo'=>149, 'numero'=>'59', 'localidad'=>'Río Negro - Ing. Luis A. Huergo'],
            ['codigo'=>150, 'numero'=>'59', 'localidad'=>'Río Negro - Mainqué'],
            ['codigo'=>151, 'numero'=>'58', 'localidad'=>'Río Negro - Viedma'],
            ['codigo'=>152, 'numero'=>'59', 'localidad'=>'Río Negro - Villa Regina'],
            ['codigo'=>153, 'numero'=>'60', 'localidad'=>'Río Negro - Zona Nº 1'],
            ['codigo'=>154, 'numero'=>'57', 'localidad'=>'Río Negro - Zona Nº 2'],

            ['codigo'=>155, 'numero'=>'62', 'localidad'=>'Salta'],
            ['codigo'=>156, 'numero'=>'D3', 'localidad'=>'Salta - Gral. San Martín - Tartagal'],
            ['codigo'=>157, 'numero'=>'61', 'localidad'=>'Salta - Gran Salta'],
            ['codigo'=>158, 'numero'=>'D0', 'localidad'=>'Salta - Los Andes'],
            ['codigo'=>159, 'numero'=>'C8', 'localidad'=>'Salta - Orán - San Ramón de la Nueva Orán'],
            ['codigo'=>160, 'numero'=>'D4', 'localidad'=>'Salta - Resto Gral. San Martín'],
            ['codigo'=>161, 'numero'=>'C9', 'localidad'=>'Salta - Resto Orán'],
            ['codigo'=>162, 'numero'=>'D2', 'localidad'=>'Salta - Rivadavia'],
            ['codigo'=>163, 'numero'=>'D1', 'localidad'=>'Salta - Santa Victoria'],

            ['codigo'=>164, 'numero'=>'64', 'localidad'=>'San Juan'],
            ['codigo'=>165, 'numero'=>'63', 'localidad'=>'San Juan - Gran San Juan'],

            ['codigo'=>166, 'numero'=>'66', 'localidad'=>'San Luis'],
            ['codigo'=>167, 'numero'=>'65', 'localidad'=>'San Luis - Ciudad de San Luis'],

            ['codigo'=>168, 'numero'=>'69', 'localidad'=>'Santa Cruz'],
            ['codigo'=>169, 'numero'=>'67', 'localidad'=>'Santa Cruz - Caleta Olivia'],
            ['codigo'=>170, 'numero'=>'68', 'localidad'=>'Santa Cruz - Río Gallegos'],

            ['codigo'=>171, 'numero'=>'73', 'localidad'=>'Santa Fe - 9 de Julio'],
            ['codigo'=>172, 'numero'=>'70', 'localidad'=>'Santa Fe - Gral. Obligado'],
            ['codigo'=>173, 'numero'=>'75', 'localidad'=>'Santa Fe - Resto de la Provincia'],
            ['codigo'=>174, 'numero'=>'71', 'localidad'=>'Santa Fe - San Javier'],
            ['codigo'=>175, 'numero'=>'72', 'localidad'=>'Santa Fe - Santo Tomé'],
            ['codigo'=>176, 'numero'=>'74', 'localidad'=>'Santa Fe - Vera'],

            ['codigo'=>177, 'numero'=>'76', 'localidad'=>'Santiago del Estero - Ciudad de Santiago del Estero'],
            ['codigo'=>178, 'numero'=>'76', 'localidad'=>'Santiago del Estero - La Banda'],
            ['codigo'=>179, 'numero'=>'77', 'localidad'=>'Santiago del Estero - Ojo de Agua'],
            ['codigo'=>180, 'numero'=>'78', 'localidad'=>'Santiago del Estero - Quebrachos'],
            ['codigo'=>181, 'numero'=>'80', 'localidad'=>'Santiago del Estero - Resto de la Provincia'],
            ['codigo'=>182, 'numero'=>'79', 'localidad'=>'Santiago del Estero - Rivadavia'],

            ['codigo'=>183, 'numero'=>'83', 'localidad'=>'Tierra del Fuego'],
            ['codigo'=>184, 'numero'=>'81', 'localidad'=>'Tierra del Fuego - Río Grande'],
            ['codigo'=>185, 'numero'=>'82', 'localidad'=>'Tierra del Fuego - Ushuaia'],

            ['codigo'=>186, 'numero'=>'85', 'localidad'=>'Tucumán'],
            ['codigo'=>187, 'numero'=>'84', 'localidad'=>'Tucumán - Gran Tucumán'],
        ];

        foreach ($zonas as $zona) {
            SicossZona::updateOrCreate(
                ['codigo' => $zona['codigo']],
                [
                    'numero'  => $zona['numero'],
                    'detalle' => $zona['localidad'],
                ]
            );
        }
    }
}
