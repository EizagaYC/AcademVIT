<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use App\Models\Permission;
use App\Models\Role;

class RoleController extends Controller
{   
    public function __construct()
    {
        $this->middleware('auth');
    }
    public function index()
    {
        Gate::authorize('haveaccess','role.index');
        $permissions = Permission::get();
        $roles = Role::orderBy('id','desc')->paginate();
        return view('admin.roles.index', compact('roles','permissions'));
    }
    
    public function store(Request $request)
    {

        $this->authorize('haveaccess','role.create');

        $request->validate([

            'name'          => 'required|max:20|unique:roles,name',
            'slug'          => 'required|max:20|unique:roles,slug',
            'full-access'   => 'required|in:yes,no',

        ]);

        $role = Role::create($request->all());

        # Verificar si se seleccionaron permisos y asignarlos al nuevo rol
        if($request->get('permission')):
            $role->permissions()->sync($request->get('permission'));
        endif;

        return redirect()->route('role.index')->with('success','Rol registrado satisfactoriamente.');
    }
    public function edit(Role $role)
    {

        $this->authorize('haveaccess','role.edit');

        $permission_role = [];
        $permissions = Permission::get();
        foreach ($role->permissions as $permission):
            $permission_role[] = $permission->id;
        endforeach;
        return view('admin.roles.edit',compact('role','permissions','permission_role'));
    }

    public function update(Request $request, Role $role)
    {

        $this->authorize('haveaccess','role.edit');

        $request->validate([

            'name'          => 'required|max:20|unique:roles,name,'.$role->id,
            'slug'          => 'required|max:20|unique:roles,slug,'.$role->id,
            'full-access'   => 'required|in:yes,no'

        ]);

        $role->update($request->all());

        # Asignarlos al nuevo rol
        $role->permissions()->sync($request->get('permission'));

        return redirect()->route('role.index')->with('success','Rol actualizado satisfactoriamente.');
    }

    public function destroy(Role $role)
    {

        $this->authorize('haveaccess','role.destroy');

        if(count($role->users) >= 1){
            return redirect()->route('role.index')->with('danger','Este rol se encuentra en uso.');
        }else{
            $role->delete();
            return redirect()->route('role.index')->with('success','Rol eliminado satisfactoriamente.');
        }
    }
}
