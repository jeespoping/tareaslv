<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class MaestrosMatrixTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_permisos()
    {
        $user = $this->userAuthenticated();
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/MaestrosMatrix/permisos');

        $response->assertStatus(200);
    }

    public function test_relations()
    {
        $user = $this->userAuthenticated();
        Sanctum::actingAs($user, ['*']);

        $data = [
            "campo" => "0024",
            "codigo" => "000006",
            "comentarios" => "2-000248-0001-0002",
            "descripcion" => "Profab",
            "medico" => "cliame",
            "posicion" => 24,
            "tipo" => "18"
        ];

        $response = $this->postJson('/api/MaestrosMatrix/relations', $data);

        $response->assertStatus(200);
    }

    public function test_selects()
    {
        $user = $this->userAuthenticated();
        Sanctum::actingAs($user, ['*']);
        
        $data = [
            "campo" => "0004",
            "codigo" => "000070",
            "comentarios" => "013-TIPO DE LIQUIDACION",
            "descripcion" => "Proemptfa",
            "medico" => "cliame",
            "posicion" => 4,
            "tipo" => "5"
        ];

        $response = $this->postJson('/api/MaestrosMatrix/selects', $data);

        $response->assertStatus(200);
    }

    public function test_datos()
    {
        $user = $this->userAuthenticated();
        Sanctum::actingAs($user, ['*']);

        $data = ["tabla" => "cliame_000006"];
        $response = $this->postJson('/api/MaestrosMatrix/datos', $data);

        $response->assertStatus(200);
    }

    public function test_datos_with_page()
    {
        $user = $this->userAuthenticated();
        Sanctum::actingAs($user, ['*']);

        $data = ["tabla" => "cliame_000006"];
        $page = 10;
        $response = $this->postJson('/api/MaestrosMatrix/datos?page='.$page, $data);

        $response->assertStatus(200);
    }

    public function userAuthenticated(){
        return User::where('codigo', '03150')->first();
    }
}
