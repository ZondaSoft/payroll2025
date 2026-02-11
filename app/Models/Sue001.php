<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Observers\Sue001Observer;

class Sue001 extends Model
{
    protected $table = 'sue001s';
    
    // Validaciones
	public static $messages = [
		'detalle.required'=>'El nombre es obligatorio',
		'detalle.min'=>'El nombre debe tener al menos 3 caracteres.'
		];

	public static $rules = [
		'detalle'=>'required|min:3',
		'detalle'=>'max:200'
		];

	protected $fillable = ['codigo','detalle','nombres','num_doc','cuil','nacionali','fecha_naci','est_civil','sexo',
		'domici','email','tel1','alta','baja','funcion','situacion','salud','cod_obraso','cod_sindic','cod_centro','area',
		'convenio','zona','tipo_jornada','posicion','empresa','locali','grupo_emp','codsector','jornada_id','cod_jerarq','cod_categ',
		'bruto','bruto_azul','reloj_usa',
		'obra_sijp', 'sicoss_activ', 'sicoss_situa', 'sicoss_modal', 'sicoss_condi', 'sicoss_sini', 'sicoss_zona', 'sicoss_reduccion', 
		'sicoss_cob_scvo', 'sicoss_porc_reduc', 'sicoss_conyuge', 'sicoss_hijos', 'sicoss_adherentes'
	];

	protected $guarded = ['id','_token' ]; // every field to protect

	// protected $casts = [
	// 	'alta'        => 'date:Y-m-d',
	// 	'fecha_naci'  => 'date:Y-m-d',
	// 	'ultima_act'  => 'date:Y-m-d',
	// 	'fecha_vto'   => 'date:Y-m-d',
	// ];

	// $legajos -> Sectores
	public function sector()
	{
		return $this->belongsTo(Sue011::class,'codsector' , 'codigo');
	}

	public function jornada()
	{
		return $this->belongsTo(Sue010::class,'jornada_id', 'id');
	}

    public function jerarquia()
	{
		return $this->belongsTo(Sue014::class,'cod_jerarq' , 'codigo');
	}

	// $legajos -> Centros de costo
	public function ccosto()
	{
		return $this->belongsTo(Sue030::class,'cod_centro', 'codigo');
	}

	// $legajos -> Provincias
	public function provincia()
	{
		return $this->belongsTo(Sue012::class,'provin');
	}

	// $legajos -> Tipos/modalidad de contrataciones
	public function modalidades()
	{
		return $this->belongsTo(Sue107::class,'mod_cto');
	}


	// $legajos -> Tipos/modalidad de contrataciones
	public function bancos()
	{
		return $this->belongsTo(Fza002::class,'banco');
	}


	// $legajos -> convenios colectivos
	public function convenios()
	{
		return $this->belongsTo(Sue007::class,'convenio', 'codigo');
	}

	// Accesor: $empleado->nom_grupo_empresario
    public function getNomConvenioAttribute()
    {
        return optional($this->convenios)->detalle . ' (' . (optional($this->convenios)->codigo ?? '') . ')' ?? 'Sin convenio asignado';
    }

	// $legajos -> categorias
	public function categorias()
	{
		return $this->belongsTo(Sue006::class,'cod_categ' , 'codigo');
	}

	// $category->products
  	public function products()
  	{
  		return $this->belongsTo(Sue028::class);
  	}

	// Filtros y busquedas
	public function scopeSearch($query, $dni, $codsector, $codcateg, $order, $cod_centro, $grupo_emp)
	{
		// Sin filtros
		if ($codsector == null and $codcateg == null and $dni == null and $cod_centro == null and $grupo_emp == null) {
			return $query->select('sue001s.id','sue001s.codigo','sue001s.detalle','sue001s.nombres','sue001s.grupo_emp','sue001s.codsector','sue001s.cod_categ','sue001s.cod_centro','sue001s.cod_jerarq','sue001s.convenio','sue001s.alta','sue001s.reloj_usa')
                            ->Where('sue001s.codigo','>',0)
							->WhereNull('baja')
							->orderBy('codigo');
		}

		// Grupo empresario
		if ($codsector == null and $codcateg == null and $dni == null and $cod_centro == null and $grupo_emp != null) {
			return $query->select('sue001s.id','sue001s.codigo','sue001s.detalle','sue001s.nombres','sue001s.grupo_emp','sue001s.codsector','sue001s.cod_categ','sue001s.cod_centro','sue001s.cod_jerarq','sue001s.convenio','sue001s.alta','sue001s.reloj_usa')
                            ->Where('sue001s.codigo','>',0)
							->Where('sue001s.grupo_emp', 'LIKE', "%{$grupo_emp}%")
							->WhereNull('baja')
							->orderBy('codigo');
		}

		// Solo Sector
		if ($codsector != null and $codcateg == null and $dni == null) {
			return $query->select('sue001s.id','sue001s.codigo','sue001s.detalle','sue001s.nombres','sue001s.grupo_emp','sue001s.codsector','sue001s.cod_categ','sue001s.cod_centro','sue001s.cod_jerarq','sue001s.convenio','sue001s.alta','sue001s.reloj_usa')
							->Where('sue001s.codigo','>',0)
							->WhereNull('baja')
							->Where('codsector', 'LIKE', "%{$codsector}%")
							->orderBy('codigo');

		// Solo centro de costo
		} elseif ($cod_centro != null and $codsector == null and $codcateg == null and $dni == null) {
			return $query->select('sue001s.id','sue001s.codigo','sue001s.detalle','sue001s.nombres','sue001s.grupo_emp','sue001s.codsector','sue001s.cod_categ','sue001s.cod_centro','sue001s.cod_jerarq','sue001s.convenio','sue001s.alta','sue001s.reloj_usa')
							->Where('sue001s.codigo','>',0)
							->WhereNull('baja')
							->Where('cod_centro', 'LIKE', "%{$cod_centro}%")
							->orderBy('codigo');

		// Solo categoria
		} elseif ($codsector == null and $codcateg != null and $dni == null) {
			return $query->select('sue001s.id','sue001s.codigo','sue001s.detalle','sue001s.nombres','sue001s.grupo_emp','sue001s.codsector','sue001s.cod_categ','sue001s.cod_centro','sue001s.cod_jerarq','sue001s.convenio','sue001s.alta','sue001s.reloj_usa')
							->Where('sue001s.codigo','>',0)
							->WhereNull('baja')
							->Where('cod_categ', 'LIKE', "%{$codcateg}%")
							->orderBy('codigo');
		// Sector y Categoria
		} elseif ($codsector != null and $codcateg != null and $dni == null) {
			return $query->select('sue001s.id','sue001s.codigo','sue001s.detalle','sue001s.nombres','sue001s.grupo_emp','sue001s.codsector','sue001s.cod_categ','sue001s.cod_centro','sue001s.cod_jerarq','sue001s.convenio','sue001s.alta','sue001s.reloj_usa')
							->Where('sue001s.codigo','>',0)
							->Where('sue001s.codsector', 'LIKE', "%{$codsector}%")
							->Where('sue001s.cod_categ', 'LIKE', "%{$codcateg}%")
							->WhereNull('baja')
							->orderBy('codigo');
		// Solo DNI
		} elseif ($codsector == null and $codcateg == null and $dni != null) {
			return $query->select('sue001s.id','sue001s.codigo','sue001s.detalle','sue001s.nombres','sue001s.grupo_emp','sue001s.codsector','sue001s.cod_categ','sue001s.cod_centro','sue001s.cod_jerarq','sue001s.convenio','sue001s.alta','sue001s.reloj_usa')
							->Where('sue001s.codigo', $dni)
							->WhereNull('baja')
							->orderBy('codigo');
		}
	}

	// Filtros y busquedas
	public function scopeSearch_noid($query, $codsector, $codcateg, $order)
	{
		if ($codsector == null and $codcateg == null) {
			return $query->select('sue001s.codigo','sue001s.codsector','sue001s.reloj_usa',\DB::raw("CONCAT(detalle , ' ' , nombres) as apynom",'sue001s.grupo_emp','sue001s.cod_centro','sue001s.cod_jerarq','sue001s.convenio'))
                            ->Where('sue001s.codigo','>',0)
							->WhereNull('baja')
							->orderBy('codigo');
		}

		// Solo Sector
		if ($codsector != null and $codcateg == null) {
			return $query->select('sue001s.codigo','sue001s.codsector','sue001s.reloj_usa',\DB::raw("CONCAT(detalle , ' ' , nombres) as apynom",'sue001s.grupo_emp','sue001s.cod_centro','sue001s.cod_jerarq','sue001s.convenio'))
							->Where('sue001s.codigo','>',0)
							->WhereNull('baja')
							->Where('codsector', 'LIKE', "%{$codsector}%")
							->orderBy('codigo');
		// Solo categoria
		} elseif ($codsector == null and $codcateg != null) {
			return $query->select('sue001s.codigo','sue001s.codsector','sue001s.reloj_usa',\DB::raw("CONCAT(detalle , ' ' , nombres) as apynom",'sue001s.grupo_emp','sue001s.cod_centro','sue001s.cod_jerarq','sue001s.convenio'))
							->Where('sue001s.codigo','>',0)
							->WhereNull('baja')
							->Where('cod_categ', 'LIKE', "%{$codcateg}%")
							->orderBy('codigo');
			// Sector y Categoria
		} elseif ($codsector != null and $codcateg != null) {
			return $query->select('sue001s.codigo','sue001s.codsector','sue001s.reloj_usa',\DB::raw("CONCAT(detalle , ' ' , nombres) as apynom",'sue001s.grupo_emp','sue001s.cod_centro','sue001s.cod_jerarq','sue001s.convenio'))
							->Where('sue001s.codigo','>',0)
							->Where('sue001s.codsector', 'LIKE', "%{$codsector}%")
							->Where('sue001s.cod_categ', 'LIKE', "%{$codcateg}%")
							->WhereNull('baja')
							->orderBy('codigo');
		}
	}

	// Scope usado en las busquedas
	public function scopeName($query, $name)
	{
		//dd("scope :" . $name);

		if ($name != "")
		{
			$query->where(\DB::raw("CONCAT(codigo,' ', detalle , ' ' , nombres)"), "LIKE" , "%$name%")
								->WhereNull('baja');

			//dd($query);
		}
	}

	public static function findByNameOrEmail($term)
  	{
      //return static::select('detalle','domici','cuil','funcion','codigo')
      //    ->where('codigo', 'LIKE', "%$term%")
      //    ->orWhere('detalle', 'LIKE', "%$term%")
      //    ->get();
         return static::select(\DB::raw("CONCAT(detalle,' ',nombres)  AS detalle"),'domici','cuil','funcion','codsector','grupo_emp','convenio','id',\DB::raw("CONCAT(codigo)"))
          		->where(\DB::raw("CONCAT(codigo, ' ' ,detalle,' ', nombres)"), "LIKE" , "%$term%")
				->WhereNull('baja')
               	->get();
	}

	// sue001s.grupo_emp -> sue086s.codigo
    public function grupoEmpresario()
    {
        return $this->belongsTo(Sue086::class, 'grupo_emp', 'codigo');
    }

    // Accesor: $empleado->nom_grupo_empresario
    public function getNomGrupoEmpresarioAttribute()
    {
        return optional($this->grupoEmpresario)->detalle ?? 'Sin grupo empresario asignado';
    }

	public function getNomSectorAttribute()
    {
		if ($this->codsector != null) {
            return $this->sector->detalle;
        }

        return 'Sin sector asignado';
	}

	public function getNomJerarquiaAttribute()
	{
		if ($this->cod_jerarq != null) {
            return $this->jerarquia->detalle;
        }

        return 'Sin jerarquia asignada';
	}

	public function getNomCentroAttribute()
    {
		if ($this->cod_centro != null) {
            return $this->ccosto->detalle;
        }

        return 'Sin centro asignado';
	}

	public function getNomCategoriaAttribute()
    {
		if ($this->cod_categ != null) {
            return $this->categorias->detalle;
        }

        return 'Sin categoria asignada';
	}

	public function getLocaliNameDotAttribute()
    {
		$nameLocali = 'Localidad sin registrar' . '';

		if ($this->locali != null) {
			$localidad = $this->belongsTo(Sue019::class, 'locali', 'codigo')->first();

			if ($localidad != null) {
				$nameLocali = $localidad->detalle .'......................................................................';
			}

        }

        return $nameLocali;
    }

	public function recibos() {
        // sue090s.legajo (FK) -> sue001s.codigo (UK)
        return $this->hasMany(Sue090::class, 'legajo', 'codigo');
    }

	public function getFotoUrlAttribute()
	{
		// Foto real por código
		$relative = "img/personal/{$this->codigo}.jpg";
		$absolute = public_path($relative);

		if (is_file($absolute)) {
			return "/{$relative}";
		}

		// Si no existe, mostrar imagen por sexo
		$sexo = strtoupper((string) ($this->sue001->sexo ?? $this->sexo ?? ''));

		return $sexo === 'F'
			? "/img/personal/default_user02.png"
			: "/img/personal/default_user01.png";
	}

    // Para que /choferes/{codigo} funcione con Route Model Binding
    public function getRouteKeyName(): string
    {
        return 'codigo';
    }

    // Scope reutilizable para “activos”
    public function scopeActivosChofer($q) {
        return $q->whereIn('cod_categ', ['007','017'])
                 ->whereNull('baja');
    }

	protected static function booted()
	{
		static::observe(Sue001Observer::class);
	}
}
