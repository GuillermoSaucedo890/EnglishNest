import { TestBed } from '@angular/core/testing';

import { Inscripcion } from './inscripcion';

describe('Inscripcion', () => {
  let service: Inscripcion;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(Inscripcion);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
